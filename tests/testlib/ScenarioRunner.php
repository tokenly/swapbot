<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Events\CustomerAddedToSwap;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Repositories\TransactionRepository;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\parse;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\XChainClient\Mock\MockBuilder;
use \PHPUnit_Framework_Assert as PHPUnit;
use Mockery as m;

/**
*  ScenarioRunner
*/
class ScenarioRunner
{

    use DispatchesCommands;

    var $xchain_mock_recorder = null;

    function __construct(Application $app, BotHelper $bot_helper, UserHelper $user_helper, CustomerHelper $customer_helper, TransactionRepository $transaction_repository, BotLedgerEntryRepository $bot_ledger_entry_repository, BotEventRepository $bot_event_repository, BotRepository $bot_repository, SwapRepository $swap_repository, BotLedgerEntryHelper $bot_ledger_entry_helper, MockBuilder $mock_builder) {
        $this->app                         = $app;
        $this->bot_helper                  = $bot_helper;
        $this->user_helper                 = $user_helper;
        $this->customer_helper             = $customer_helper;
        $this->transaction_repository      = $transaction_repository;
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
        $this->bot_event_repository        = $bot_event_repository;
        $this->bot_repository              = $bot_repository;
        $this->swap_repository             = $swap_repository;
        $this->bot_ledger_entry_helper     = $bot_ledger_entry_helper;
        $this->mock_builder                = $mock_builder;

    }

    public function init($test_case) {
        if (!isset($this->inited)) {
            $this->inited = true;

            // setup mock xchain
            $this->xchain_mock_recorder = $this->mock_builder->installXChainMockClient($test_case);

            // setup mock mailer
            $this->mock_mailer_recorder = $this->installMockMailer();

            // clear bot events
            $this->clearBotEvents();
        }


        return $this;
    }

    public function runScenarioByNumber($scenario_number) {
        $scenario_data = $this->loadScenarioByNumber($scenario_number);
        return $this->runScenario($scenario_data);
    }

    public function loadScenarioByNumber($scenario_number) {
        $filename = "scenario".sprintf('%02d', $scenario_number).".yml";
        return $this->loadScenario($filename);
    }

    public function loadScenario($filename) {
        $filepath = base_path().'/tests/fixtures/scenarios/'.$filename;
        return Yaml::parse(file_get_contents($filepath));
    }

    public function runScenario($scenario_data) {
        // set up the scenario
        $bots = $this->addBots($scenario_data['bots']);

        // setup mock quotebot
        $this->quotebot_recorder = $this->installMockQuotebot(isset($scenario_data['quotebot']) ? $scenario_data['quotebot'] : null);

        $events = $this->normalizeScenarioEvents($scenario_data);
        foreach($events as $event) {
            $this->executeScenarioEvent($event, $scenario_data);
        }
    }

    public function validateScenario($scenario_data) {
        if (isset($scenario_data['expectedXChainCalls'])) { $this->validateExpectedXChainCalls($scenario_data['expectedXChainCalls']); }
        if (isset($scenario_data['expectedBotEvents'])) { $this->validateExpectedBotEvents($scenario_data['expectedBotEvents']); }
        if (isset($scenario_data['expectedTransactionModels'])) { $this->validateExpectedTransactionModels($scenario_data['expectedTransactionModels']); }
        if (isset($scenario_data['expectedBotLedgerEntries'])) { $this->validateExpectedBotLedgerEntryModels($scenario_data['expectedBotLedgerEntries']); }
        if (isset($scenario_data['expectedBotModels'])) { $this->validateExpectedBotModels($scenario_data['expectedBotModels']); }
        if (isset($scenario_data['expectedSwapModels'])) { $this->validateExpectedSwapModels($scenario_data['expectedSwapModels']); }
        if (isset($scenario_data['expectedEmails'])) { $this->validateExpectedEmails($scenario_data['expectedEmails']); }
    }




    
    ////////////////////////////////////////////////////////////////////////
    // Scenario Events

    protected function normalizeScenarioEvents($scenario_data) {
        if (isset($scenario_data['events'])) {
            return $scenario_data['events'];
        } else {
            // just do all xchain notifications by default
            $events = [['type' => 'xchainNotification', 'startOffset' => 0, ]];
            return $events;
        }

    }

    protected function executeScenarioEvent($event, $scenario_data) {
        $type = $event['type'];
        $method = "executeScenarioEvent_{$type}";
        if (method_exists($this, $method)) {
            call_user_func([$this, $method], $event, $scenario_data);
        } else {
            throw new Exception("Method not found: $method", 1);
        }
    }

    protected function executeScenarioEvent_xchainNotification($event, $scenario_data) {
        // process notifications
        $all_xchain_notifications = $scenario_data['xchainNotifications'];
        $xchain_notifications = $this->resolveOffset($all_xchain_notifications, $event);

        foreach ($xchain_notifications as $raw_notification) {
            $notification = $raw_notification;
            $meta = $raw_notification['meta'];
            unset($notification['meta']);

            $notification = array_replace_recursive($this->loadBaseFilename($raw_notification, "notifications"), $notification);
            // echo "\$notification:\n".json_encode($notification, 192)."\n";

            // ensure the notification has a notificationID (for duplicate protection)
            if (!isset($notification['notificationId'])) {
                $notification['notificationId'] = md5(json_encode($notification));
            }


            // look for exceptions trigger
            if (isset($meta['xchainFailAfterRequests'])) {
                $this->mock_builder->beginThrowingExceptionsAfterCount($meta['xchainFailAfterRequests']);
            } else {
                // stop throwing exceptions
                $this->mock_builder->stopThrowingExceptions();
            }

            // process the notification
            $this->dispatch(new ReceiveWebhook($notification));

        }
    }

    protected function resolveOffset($all_xchain_notifications, $event) {
        if (isset($event['offset'])) {
            return [$all_xchain_notifications[$event['offset']]];
        }
        if (isset($event['startOffset'])) {
            $xchain_notifications = array_slice($all_xchain_notifications, $event['startOffset'], isset($event['length']) ? $event['length'] : null);
            return $xchain_notifications;
        }

        throw new Exception("Unable to resolve offset for event: ".json_encode($event, 192), 1);
    }

    protected function executeScenarioEvent_addCustomer($event, $scenario_data) {
        $customer_attributes = array_replace_recursive($this->loadDataByBaseFilename($event['baseFilename'], "customers"), isset($event['data']) ? $event['data'] : []);
        $swap = $this->resolveSwap($event, $scenario_data);

        $customer = $this->customer_helper->newSampleCustomer($swap, $customer_attributes);
        Event::fire(new CustomerAddedToSwap($customer, $swap));
    }

    protected function resolveSwap($event, $scenario_data) {
        // find the first available swap
        $all_swaps = $this->swap_repository->findAll();
        if (count($all_swaps) > 1) {
            throw new Exception("Multiple swaps found.  Don't know which to attach to.", 1);
        }
        return $all_swaps[0];
    }


    ////////////////////////////////////////////////////////////////////////
    // Bots

    protected function addBots($bots) {
        if (!isset($this->bot_models)) { $this->bot_models = []; }
        foreach($bots as $bot_entry) {
            $bot_attributes = $this->loadBaseFilename($bot_entry, "bots");
            unset($bot_entry['meta']);
            $bot_attributes = array_replace_recursive($bot_attributes, $bot_entry);
            $payments = isset($bot_attributes['payments']) ? $bot_attributes['payments'] : null;
            unset($bot_attributes['payments']);
            $bot = $this->bot_helper->newSampleBot($this->getSampleUser(), $bot_attributes);
            $this->bot_models[] = $bot;

            if ($payments) {
                foreach($payments as $payment) {
                    $this->addPayment($bot, $payment);
                }
            }
        }
        return $this->bot_models;
    }

    protected function addPayment($bot, $payment) {
        $is_credit = isset($payment['credit']) ? $payment['credit'] : true;
        $amount = $payment['amount'];

        $bot_event = app('BotEventHelper')->newSampleBotEvent($bot, ['event' => ['name' => 'test.payment.setup', 'msg' => 'scenario starting payment']]);

        if ($is_credit) {
            $this->bot_ledger_entry_repository->addCredit($bot, $amount, $bot_event);
        } else {
            $this->bot_ledger_entry_repository->addDebit($bot, $amount, $bot_event);
        }
    }


    protected function getSampleUser() {
        if (!isset($this->sample_user)) {
            $this->sample_user = $this->user_helper->getSampleUser();
        }
        return $this->sample_user;
    }

    protected function loadBaseFilename($entry, $fixtures_folder) {
        if (isset($entry['meta']) AND isset($entry['meta']['baseFilename'])) {
            $base_filename = $entry['meta']['baseFilename'];
            return $this->loadDataByBaseFilename($base_filename, $fixtures_folder);
        } else {
            return [];
        }
    }

    protected function loadDataByBaseFilename($base_filename, $fixtures_folder) {
        $directory = base_path().'/tests/fixtures/'.trim($fixtures_folder, '/');
        $filepath = $directory.'/'.$base_filename;
        PHPUnit::assertTrue(file_exists($filepath), "Filepath did not exist: {$filepath}.  Files were: ".json_encode(scandir($directory), 192));

        $text = file_get_contents($filepath);
        if (substr($base_filename, -5) == '.json') {
            return json_decode($text, true);
        }
        if (substr($base_filename, -4) == '.yml') {
            return Yaml::parse($text);
        }
        throw new Exception("Unknown filename $filename", 1);
    }

    ////////////////////////////////////////////////////////////////////////
    // ExpectedBotEvents

    protected function clearBotEvents() {
        foreach ($this->bot_event_repository->findAll() as $bot_event) {
            $this->bot_event_repository->delete($bot_event);
        }
    }

    protected function validateExpectedBotEvents($expected_bot_events) {
        $actual_bot_events = [];
        foreach ($this->bot_event_repository->findAll() as $bot_event) {
            $event_vars = $bot_event->toArray()['event'];
            // ignore the test payment setup
            if ($event_vars['name'] == 'test.payment.setup') { continue; }
            $actual_bot_events[] = $event_vars;
        }
        // Log::debug("\$actual_bot_events=".json_encode($actual_bot_events, 192));

        foreach ($expected_bot_events as $offset => $raw_expected_bot_event) {
            $actual_bot_event = isset($actual_bot_events[$offset]) ? $actual_bot_events[$offset] : null;

            $expected_bot_event = $raw_expected_bot_event;
            unset($expected_bot_event['meta']);
            $expected_bot_event = array_replace_recursive($this->loadBaseFilename($raw_expected_bot_event, "bot_events"), $expected_bot_event);

            $expected_bot_event = $this->normalizeExpectedBotEvent($expected_bot_event, $actual_bot_event);
            
            $this->validateExpectedBotEvent($expected_bot_event, $actual_bot_event, $offset+1);
        }

        // make sure the counts are the same
        PHPUnit::assertCount(count($expected_bot_events), $actual_bot_events, "Did not find the correct number of Bot Events.  \$actual_bot_events:".json_encode($actual_bot_events, 192));
    }

    protected function validateExpectedBotEvent($expected_bot_event, $actual_bot_event, $number) {
        PHPUnit::assertNotEmpty($actual_bot_event, "Missing bot event #{$number}: ".json_encode($expected_bot_event, 192));
        PHPUnit::assertEquals($expected_bot_event, $actual_bot_event, "ExpectedBotEvent #{$number} mismatch ({$actual_bot_event['name']})");
    }




    protected function normalizeExpectedBotEvent($expected_bot_event, $actual_bot_event) {
        $normalized_expected_bot_event = [];

        // placeholder
        $normalized_expected_bot_event = $expected_bot_event;

        ///////////////////
        // EXPECTED
        // 'msg' is no longer saved to the db
        $expected_fields = ['name',];
        foreach ($expected_fields as $field) {
            $normalized_expected_bot_event[$field] = isset($expected_bot_event[$field]) ? $expected_bot_event[$field] : '[none provided]';
        }
        ///////////////////

        ///////////////////
        // NOT REQUIRED
        $optional_fields = ['txid','file','line','transactionId','swapId',];
        foreach ($optional_fields as $field) {
            if (isset($expected_bot_event[$field])) { $normalized_expected_bot_event[$field] = $expected_bot_event[$field]; }
                else if (isset($actual_bot_event[$field])) { $normalized_expected_bot_event[$field] = $actual_bot_event[$field]; }
        }
        ///////////////////

        ///////////////////
        // JUST NEEDS TO EXIST
        $must_exist_fields = ['completedAt',];
        foreach ($must_exist_fields as $field) {
            if (isset($actual_bot_event[$field])) { $normalized_expected_bot_event[$field] = $actual_bot_event[$field]; }
        }
        ///////////////////



        // ///////////////////
        // // Special
        // // build satoshis
        // $normalized_expected_bot_event['quantitySat'] = CurrencyUtil::valueToSatoshis($normalized_expected_bot_event['quantity']);
        // // blockhash
        // if (isset($expected_bot_event['blockhash'])) {
        //     $normalized_expected_bot_event['bitcoinTx']['blockhash'] = $expected_bot_event['blockhash'];
        // }
        // ///////////////////



        return $normalized_expected_bot_event;
    }




    ////////////////////////////////////////////////////////////////////////
    // ExpectedXChainCalls

    protected function validateExpectedXChainCalls($expected_xchain_calls) {
        if ($expected_xchain_calls === 'none') {
            $count = count($this->xchain_mock_recorder->calls);
            PHPUnit::assertEmpty($this->xchain_mock_recorder->calls, "Found ".$count." unexpected XChain call".($count==1?'':'s')."");
            return;
        }

        $actual_xchain_calls = $this->xchain_mock_recorder->calls;
        foreach ($expected_xchain_calls as $offset => $raw_expected_xchain_call) {
            $actual_xchain_call = isset($actual_xchain_calls[$offset]) ? $actual_xchain_calls[$offset] : null;

            $expected_xchain_call = $raw_expected_xchain_call;
            unset($expected_xchain_call['meta']);
            $expected_xchain_call = array_replace_recursive($this->loadBaseFilename($raw_expected_xchain_call, "xchain_calls"), $expected_xchain_call);

            $expected_xchain_call = $this->normalizeExpectedXChainCall($expected_xchain_call, $actual_xchain_call);
            
            $this->validateExpectedXChainCall($expected_xchain_call, $actual_xchain_call);
        }

        // make sure the counts are the same
        PHPUnit::assertCount(count($expected_xchain_calls), $actual_xchain_calls, "Did not find the correct number of XChain calls\n\$actual_xchain_calls=".json_encode($actual_xchain_calls, 192));

    }

    protected function validateExpectedXChainCall($expected_xchain_call, $actual_xchain_call) {
        PHPUnit::assertNotEmpty($actual_xchain_call, "Missing xchain call ".json_encode($expected_xchain_call, 192));
        PHPUnit::assertEquals($expected_xchain_call, $actual_xchain_call, "ExpectedXChainCall mismatch");
    }




    protected function normalizeExpectedXChainCall($expected_xchain_call, $actual_xchain_call) {
        $normalized_expected_xchain_call = [];

        // placeholder
        $normalized_expected_xchain_call = $expected_xchain_call;


        // ///////////////////
        // // EXPECTED
        // foreach (['txid','quantity','asset','notifiedAddress','event','network',] as $field) {
        //     if (isset($expected_xchain_call[$field])) { $normalized_expected_xchain_call[$field] = $expected_xchain_call[$field]; }
        // }
        // ///////////////////

        // ///////////////////
        // // OPTIONAL
        // foreach (['confirmations','confirmed','counterpartyTx','bitcoinTx','transactionTime','notificationId','notifiedAddressId','webhookEndpoint','blockSeq','confirmationTime',] as $field) {
        //     if (isset($expected_xchain_call[$field])) { $normalized_expected_xchain_call[$field] = $expected_xchain_call[$field]; }
        //         else if (isset($actual_notification[$field])) { $normalized_expected_xchain_call[$field] = $actual_notification[$field]; }
        // }
        // ///////////////////

        // ///////////////////
        // // Special
        // // build satoshis
        // $normalized_expected_xchain_call['quantitySat'] = CurrencyUtil::valueToSatoshis($normalized_expected_xchain_call['quantity']);
        // // blockhash
        // if (isset($expected_xchain_call['blockhash'])) {
        //     $normalized_expected_xchain_call['bitcoinTx']['blockhash'] = $expected_xchain_call['blockhash'];
        // }
        // ///////////////////



        return $normalized_expected_xchain_call;
    }


    ////////////////////////////////////////////////////////////////////////
    // ExpectedTransactionModels

    protected function validateExpectedTransactionModels($expected_transaction_models) {
        // if ($expected_transaction_models === 'none') {
        //     PHPUnit::assertEmpty($this->xchain_mock_recorder->calls);
        //     return;
        // }

        $actual_transaction_models = [];
        foreach ($this->transaction_repository->findAll() as $transaction_model) {
            $actual_transaction_models[] = $transaction_model->toArray();
        }

        foreach ($expected_transaction_models as $offset => $raw_expected_transaction_model) {
            $actual_transaction_model = isset($actual_transaction_models[$offset]) ? $actual_transaction_models[$offset] : null;

            $expected_transaction_model = $raw_expected_transaction_model;
            unset($expected_transaction_model['meta']);
            $expected_transaction_model = array_replace_recursive($this->loadBaseFilename($raw_expected_transaction_model, "transaction_models"), $expected_transaction_model);

            $expected_transaction_model = $this->normalizeExpectedTransactionRecord($expected_transaction_model, $actual_transaction_model);
            
            $this->validateExpectedTransactionRecord($expected_transaction_model, $actual_transaction_model);
        }

        // make sure the counts are the same
        PHPUnit::assertCount(count($expected_transaction_models), $actual_transaction_models, "Did not find the correct number of Transaction models");

    }

    protected function validateExpectedTransactionRecord($expected_transaction_model, $actual_transaction_model) {
        PHPUnit::assertNotEmpty($actual_transaction_model, "Missing transaction model ".json_encode($expected_transaction_model, 192));
        PHPUnit::assertEquals($expected_transaction_model, $actual_transaction_model, "ExpectedTransactionRecord mismatch");
    }




    protected function normalizeExpectedTransactionRecord($expected_transaction_model, $actual_transaction_model) {
        $normalized_expected_transaction_model = [];

        // placeholder
        $normalized_expected_transaction_model = $expected_transaction_model;


        // ///////////////////
        // // EXPECTED
        // foreach (['txid','quantity','asset','notifiedAddress','event','network',] as $field) {
        //     if (isset($expected_transaction_model[$field])) { $normalized_expected_transaction_model[$field] = $expected_transaction_model[$field]; }
        // }
        // ///////////////////


        ///////////////////
        // NOT REQUIRED
        $optional_fields = ['id','bot_id','billed_event_id','xchain_notification','updated_at','created_at',];
        foreach ($optional_fields as $field) {
            if (isset($expected_transaction_model[$field])) { $normalized_expected_transaction_model[$field] = $expected_transaction_model[$field]; }
                else if (isset($actual_transaction_model[$field])) { $normalized_expected_transaction_model[$field] = $actual_transaction_model[$field]; }
                else { $normalized_expected_transaction_model[$field] = null; }
        }
        ///////////////////



        // ///////////////////
        // // Special
        // // build satoshis
        // $normalized_expected_transaction_model['quantitySat'] = CurrencyUtil::valueToSatoshis($normalized_expected_transaction_model['quantity']);
        // // blockhash
        // if (isset($expected_transaction_model['blockhash'])) {
        //     $normalized_expected_transaction_model['bitcoinTx']['blockhash'] = $expected_transaction_model['blockhash'];
        // }
        // ///////////////////



        return $normalized_expected_transaction_model;
    }


    ////////////////////////////////////////////////////////////////////////
    // ExpectedBotLedgerEntryModels

    protected function validateExpectedBotLedgerEntryModels($expected_bot_ledger_entries) {
        $actual_bot_ledger_entries = [];
        foreach ($this->bot_ledger_entry_repository->findAll() as $bot_ledger_entry_model) {
            $actual_bot_ledger_entries[] = $bot_ledger_entry_model->toArray();
        }

        foreach ($expected_bot_ledger_entries as $offset => $raw_expected_bot_ledger_entry_model) {
            $actual_bot_ledger_entry_model = isset($actual_bot_ledger_entries[$offset]) ? $actual_bot_ledger_entries[$offset] : null;

            $expected_bot_ledger_entry_model = $raw_expected_bot_ledger_entry_model;
            unset($expected_bot_ledger_entry_model['meta']);
            $expected_bot_ledger_entry_model = array_replace_recursive($this->loadBaseFilename($raw_expected_bot_ledger_entry_model, "bot_ledger_models"), $expected_bot_ledger_entry_model);

            $expected_bot_ledger_entry_model = $this->normalizeExpectedBotLedgerEntryRecord($expected_bot_ledger_entry_model, $actual_bot_ledger_entry_model);
            
            $this->validateExpectedBotLedgerEntryRecord($expected_bot_ledger_entry_model, $actual_bot_ledger_entry_model);
        }

        // make sure the counts are the same
        PHPUnit::assertCount(count($expected_bot_ledger_entries), $actual_bot_ledger_entries, "Did not find the correct number of BotLedgerEntry models");

    }

    protected function validateExpectedBotLedgerEntryRecord($expected_bot_ledger_entry_model, $actual_bot_ledger_entry_model) {
        PHPUnit::assertNotEmpty($actual_bot_ledger_entry_model, "Missing ledger entry model ".json_encode($expected_bot_ledger_entry_model, 192));
        PHPUnit::assertEquals($expected_bot_ledger_entry_model, $actual_bot_ledger_entry_model, "ExpectedBotLedgerEntryRecord mismatch");
    }




    protected function normalizeExpectedBotLedgerEntryRecord($expected_bot_ledger_entry_model, $actual_bot_ledger_entry_model) {
        $normalized_expected_bot_ledger_entry_model = [];

        // placeholder
        $normalized_expected_bot_ledger_entry_model = $expected_bot_ledger_entry_model;


        // ///////////////////
        // // EXPECTED
        // foreach (['txid','quantity','asset','notifiedAddress','event','network',] as $field) {
        //     if (isset($expected_bot_ledger_entry_model[$field])) { $normalized_expected_bot_ledger_entry_model[$field] = $expected_bot_ledger_entry_model[$field]; }
        // }
        // ///////////////////


        ///////////////////
        // NOT REQUIRED
        $optional_fields = ['id','uuid','user_id','bot_id','created_at',];
        foreach ($optional_fields as $field) {
            if (isset($expected_bot_ledger_entry_model[$field])) { $normalized_expected_bot_ledger_entry_model[$field] = $expected_bot_ledger_entry_model[$field]; }
                else if (isset($actual_bot_ledger_entry_model[$field])) { $normalized_expected_bot_ledger_entry_model[$field] = $actual_bot_ledger_entry_model[$field]; }
        }
        ///////////////////

        ///////////////////
        // JUST NEEDS TO EXIST
        $must_exist_fields = ['bot_event_id',];
        foreach ($must_exist_fields as $field) {
            if (isset($actual_bot_ledger_entry_model[$field])) { $normalized_expected_bot_ledger_entry_model[$field] = $actual_bot_ledger_entry_model[$field]; }
        }
        ///////////////////



        // ///////////////////
        // // Special
        // // build satoshis
        // $normalized_expected_bot_ledger_entry_model['quantitySat'] = CurrencyUtil::valueToSatoshis($normalized_expected_bot_ledger_entry_model['quantity']);
        // // blockhash
        // if (isset($expected_bot_ledger_entry_model['blockhash'])) {
        //     $normalized_expected_bot_ledger_entry_model['bitcoinTx']['blockhash'] = $expected_bot_ledger_entry_model['blockhash'];
        // }
        // ///////////////////



        return $normalized_expected_bot_ledger_entry_model;
    }


    ////////////////////////////////////////////////////////////////////////
    // ExpectedBotModels

    protected function validateExpectedBotModels($expected_bots) {
        $actual_bots = [];
        foreach ($this->bot_repository->findAll() as $bot_model) {
            $actual_bots[] = $bot_model->toArray();
        }

        foreach ($expected_bots as $offset => $raw_expected_bot_model) {
            $actual_bot_model = isset($actual_bots[$offset]) ? $actual_bots[$offset] : null;

            $expected_bot_model = $raw_expected_bot_model;
            unset($expected_bot_model['meta']);
            $expected_bot_model = array_replace_recursive($this->loadBaseFilename($raw_expected_bot_model, "bots"), $expected_bot_model);

            $expected_bot_model = $this->normalizeExpectedBotRecord($expected_bot_model, $actual_bot_model);
            
            $this->validateExpectedBotRecord($expected_bot_model, $actual_bot_model);
        }

        // make sure the counts are the same
        PHPUnit::assertCount(count($expected_bots), $actual_bots, "Did not find the correct number of Bot models");

    }

    protected function validateExpectedBotRecord($expected_bot_model, $actual_bot_model) {
        PHPUnit::assertNotEmpty($actual_bot_model, "Missing bot model ".json_encode($expected_bot_model, 192));
        PHPUnit::assertEquals($expected_bot_model, $actual_bot_model, "ExpectedBotRecord mismatch");
    }




    protected function normalizeExpectedBotRecord($expected_bot_model, $actual_bot_model) {
        $normalized_expected_bot_model = [];

        // placeholder
        $normalized_expected_bot_model = $expected_bot_model;


        // ///////////////////
        // // EXPECTED
        // foreach (['txid','quantity','asset','notifiedAddress','event','network',] as $field) {
        //     if (isset($expected_bot_model[$field])) { $normalized_expected_bot_model[$field] = $expected_bot_model[$field]; }
        // }
        // ///////////////////


        ///////////////////
        // NOT REQUIRED
        $optional_fields = [];
        $optional_fields = array_merge(array_keys(app('BotHelper')->sampleBotVars()), ['id','uuid','user_id','created_at','updated_at',]);
        foreach ($optional_fields as $field) {
            if (isset($expected_bot_model[$field])) { $normalized_expected_bot_model[$field] = $expected_bot_model[$field]; }
                else if (isset($actual_bot_model[$field])) { $normalized_expected_bot_model[$field] = $actual_bot_model[$field]; }
                else { $normalized_expected_bot_model[$field] = null; }
        }
        ///////////////////


        // ///////////////////
        // // Special
        // // build satoshis
        // $normalized_expected_bot_model['quantitySat'] = CurrencyUtil::valueToSatoshis($normalized_expected_bot_model['quantity']);
        // // blockhash
        // if (isset($expected_bot_model['blockhash'])) {
        //     $normalized_expected_bot_model['bitcoinTx']['blockhash'] = $expected_bot_model['blockhash'];
        // }
        // ///////////////////



        return $normalized_expected_bot_model;
    }


    ////////////////////////////////////////////////////////////////////////
    // ExpectedSwapModels

    protected function validateExpectedSwapModels($expected_swaps) {
        $actual_swaps = [];
        foreach ($this->swap_repository->findAll() as $swap_model) {
            $actual_swaps[] = $swap_model->toArray();
        }

        foreach ($expected_swaps as $offset => $raw_expected_swap_model) {
            $actual_swap_model = isset($actual_swaps[$offset]) ? $actual_swaps[$offset] : null;

            $expected_swap_model = $raw_expected_swap_model;
            unset($expected_swap_model['meta']);
            $expected_swap_model = array_replace_recursive($this->loadBaseFilename($raw_expected_swap_model, "swap_models"), $expected_swap_model);

            $expected_swap_model = $this->normalizeExpectedSwapRecord($expected_swap_model, $actual_swap_model);
            
            $this->validateExpectedSwapRecord($expected_swap_model, $actual_swap_model);
        }

        // make sure the counts are the same
        PHPUnit::assertCount(count($expected_swaps), $actual_swaps, "Did not find the correct number of Swap models");

    }

    protected function validateExpectedSwapRecord($expected_swap_model, $actual_swap_model) {
        PHPUnit::assertNotEmpty($actual_swap_model, "Missing swap model ".json_encode($expected_swap_model, 192));
        PHPUnit::assertEquals($expected_swap_model, $actual_swap_model, "ExpectedSwapRecord mismatch");
    }




    protected function normalizeExpectedSwapRecord($expected_swap_model, $actual_swap_model) {
        $normalized_expected_swap_model = [];

        // placeholder
        $normalized_expected_swap_model = $expected_swap_model;


        // ///////////////////
        // // EXPECTED
        // foreach (['txid','quantity','asset','notifiedAddress','event','network',] as $field) {
        //     if (isset($expected_swap_model[$field])) { $normalized_expected_swap_model[$field] = $expected_swap_model[$field]; }
        // }
        // ///////////////////


        ///////////////////
        // NOT REQUIRED
        $optional_fields = ['id','uuid','definition','transaction_id','bot_id','created_at','updated_at','completed_at',];
        foreach ($optional_fields as $field) {
            if (isset($expected_swap_model[$field])) { $normalized_expected_swap_model[$field] = $expected_swap_model[$field]; }
                // else if (isset($actual_swap_model[$field])) { $normalized_expected_swap_model[$field] = $actual_swap_model[$field]; }
                else { $normalized_expected_swap_model[$field] = $actual_swap_model[$field]; }
        }
        ///////////////////

        ///////////////////
        // JUST NEEDS TO EXIST
        $must_exist_fields = ['bot_event_id',];
        foreach ($must_exist_fields as $field) {
            if (isset($actual_swap_model[$field])) { $normalized_expected_swap_model[$field] = $actual_swap_model[$field]; }
        }

        // receipt timestamp
        if (isset($actual_swap_model['receipt']['timestamp'])) { $normalized_expected_swap_model['receipt']['timestamp'] = $actual_swap_model['receipt']['timestamp']; }
        if (isset($actual_swap_model['receipt']['completedAt'])) { $normalized_expected_swap_model['receipt']['completedAt'] = $actual_swap_model['receipt']['completedAt']; }

        ///////////////////



        // ///////////////////
        // // Special
        // // build satoshis
        // $normalized_expected_swap_model['quantitySat'] = CurrencyUtil::valueToSatoshis($normalized_expected_swap_model['quantity']);
        // // blockhash
        // if (isset($expected_swap_model['blockhash'])) {
        //     $normalized_expected_swap_model['bitcoinTx']['blockhash'] = $expected_swap_model['blockhash'];
        // }
        // ///////////////////



        return $normalized_expected_swap_model;
    }



    ////////////////////////////////////////////////////////////////////////
    // expectedEmails

    protected function validateExpectedEmails($expected_emails) {
        $actual_emails = $this->mock_mailer_recorder->emails;

        foreach ($expected_emails as $offset => $raw_expected_email_model) {
            $actual_email_model = isset($actual_emails[$offset]) ? $actual_emails[$offset] : null;

            $expected_email_model = $raw_expected_email_model;
            unset($expected_email_model['meta']);
            $expected_email_model = array_replace_recursive($this->loadBaseFilename($raw_expected_email_model, "emails"), $expected_email_model);

            $expected_email_model = $this->normalizeExpectedEmailRecord($expected_email_model, $actual_email_model);
            
            $this->validateExpectedEmailRecord($expected_email_model, $actual_email_model);
        }

        // make sure the counts are the same
        PHPUnit::assertCount(count($expected_emails), $actual_emails, "Did not find the correct number of Email models");

    }

    protected function validateExpectedEmailRecord($expected_email_model, $actual_email_model) {
        PHPUnit::assertNotEmpty($actual_email_model, "Missing email model ".json_encode($expected_email_model, 192));
        PHPUnit::assertEquals($expected_email_model, $actual_email_model, "ExpectedEmailRecord mismatch");
    }




    protected function normalizeExpectedEmailRecord($expected_email_model, $actual_email_model) {
        $normalized_expected_email_model = [];

        // placeholder
        $normalized_expected_email_model = $expected_email_model;


        // ///////////////////
        // // EXPECTED
        // foreach (['txid','quantity','asset','notifiedAddress','event','network',] as $field) {
        //     if (isset($expected_email_model[$field])) { $normalized_expected_email_model[$field] = $expected_email_model[$field]; }
        // }
        // ///////////////////


        ///////////////////
        // NOT REQUIRED
        $optional_fields = [];
        foreach ($optional_fields as $field) {
            if (isset($expected_email_model[$field])) { $normalized_expected_email_model[$field] = $expected_email_model[$field]; }
                else if (isset($actual_email_model[$field])) { $normalized_expected_email_model[$field] = $actual_email_model[$field]; }
        }
        ///////////////////

        ///////////////////
        // JUST NEEDS TO MATCH
        $must_match_fields = ['body'];
        foreach ($must_match_fields as $field) {
            if (isset($actual_email_model[$field])) {
                if (stristr($actual_email_model[$field], $normalized_expected_email_model[$field]) !== false) {
                    $normalized_expected_email_model[$field] = $actual_email_model[$field];
                }
            }
        }
        ///////////////////

        ///////////////////
        // JUST NEEDS TO EXIST
        $must_exist_fields = [];
        foreach ($must_exist_fields as $field) {
            if (isset($actual_email_model[$field])) { $normalized_expected_email_model[$field] = $actual_email_model[$field]; }
        }
        ///////////////////



        // ///////////////////
        // // Special
        // // build satoshis
        // $normalized_expected_email_model['quantitySat'] = CurrencyUtil::valueToSatoshis($normalized_expected_email_model['quantity']);
        // // blockhash
        // if (isset($expected_email_model['blockhash'])) {
        //     $normalized_expected_email_model['bitcoinTx']['blockhash'] = $expected_email_model['blockhash'];
        // }
        // ///////////////////



        return $normalized_expected_email_model;
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Mock Mailer

    public function installMockMailer() {
        // don't background emails
        Mail::setQueue(Queue::getFacadeRoot()->connection('sync'));

        // mock
        $mock = m::mock('Swift_Mailer');
        app('mailer')->setSwiftMailer($mock);

        // expect
        $mailer_recorder = new \stdClass();
        $mailer_recorder->emails = [];
        $mock
            ->shouldReceive('send')
            ->andReturnUsing(function(\Swift_Message $msg) use ($mailer_recorder) {
                $email = [
                    'subject' => $msg->getSubject(),
                    'to'      => $msg->getTo(),
                    'from'    => $msg->getFrom(),
                    'body'    => $msg->getBody(),
                ];
                $mailer_recorder->emails[] = $email;

                // app('Illuminate\Mail\Transport\LogTransport')->send($msg);
            });

        return $mailer_recorder;
    }    
    

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Mock Quotebot

    public function installMockQuotebot($rate_entries=null) {
        $quotebot_mock_builder = app('Tokenly\QuotebotClient\Mock\MockBuilder');

        if ($rate_entries !== null) {
            $quotebot_mock_builder->setMockRates($rate_entries);
        }

        $quotebot_recorder = $quotebot_mock_builder->installQuotebotMockClient();
        return $quotebot_recorder;
    }    
    


}