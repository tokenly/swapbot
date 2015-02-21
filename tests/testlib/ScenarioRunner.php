<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\TransactionRepository;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\parse;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\XChainClient\Mock\MockBuilder;
use \PHPUnit_Framework_Assert as PHPUnit;

/**
*  ScenarioRunner
*/
class ScenarioRunner
{

    use DispatchesCommands;

    var $xchain_mock_recorder = null;

    function __construct(Application $app, BotHelper $bot_helper, UserHelper $user_helper, TransactionRepository $transaction_repository, BotLedgerEntryRepository $bot_ledger_entry_repository, BotEventRepository $bot_event_repository, BotRepository $bot_repository, MockBuilder $mock_builder) {
        $this->app                         = $app;
        $this->bot_helper                  = $bot_helper;
        $this->user_helper                 = $user_helper;
        $this->transaction_repository      = $transaction_repository;
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
        $this->bot_event_repository        = $bot_event_repository;
        $this->bot_repository              = $bot_repository;
        $this->mock_builder                = $mock_builder;

    }

    public function init($test_case) {
        if (!isset($this->inited)) {
            $this->inited = true;

            // setup mock xchain
            $this->xchain_mock_recorder = $this->mock_builder->installXChainMockClient($test_case);

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
        // echo "\$bots:\n".json_encode($bots, 192)."\n";

        // process notifications
        foreach ($scenario_data['xchainNotifications'] as $raw_notification) {
            $notification = $raw_notification;
            $meta = $raw_notification['meta'];
            unset($notification['meta']);

            $notification = array_replace_recursive($this->loadBaseFilename($raw_notification, "notifications"), $notification);
            // echo "\$notification:\n".json_encode($notification, 192)."\n";

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

    public function validateScenario($scenario_data) {
        if (isset($scenario_data['expectedXChainCalls'])) { $this->validateExpectedXChainCalls($scenario_data['expectedXChainCalls']); }
        if (isset($scenario_data['expectedBotEvents'])) { $this->validateExpectedBotEvents($scenario_data['expectedBotEvents']); }
        if (isset($scenario_data['expectedTransactionModels'])) { $this->validateExpectedTransactionModels($scenario_data['expectedTransactionModels']); }
        if (isset($scenario_data['expectedBotLedgerEntries'])) { $this->validateExpectedBotLedgerEntryModels($scenario_data['expectedBotLedgerEntries']); }
        if (isset($scenario_data['expectedBotModels'])) { $this->validateExpectedBotModels($scenario_data['expectedBotModels']); }
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
            $actual_bot_events[] = $bot_event->toArray()['event'];
        }

        foreach ($expected_bot_events as $offset => $raw_expected_bot_event) {
            $actual_bot_event = isset($actual_bot_events[$offset]) ? $actual_bot_events[$offset] : null;

            $expected_bot_event = $raw_expected_bot_event;
            unset($expected_bot_event['meta']);
            $expected_bot_event = array_replace_recursive($this->loadBaseFilename($raw_expected_bot_event, "bot_events"), $expected_bot_event);

            $expected_bot_event = $this->normalizeExpectedBotEvent($expected_bot_event, $actual_bot_event);
            
            $this->validateExpectedBotEvent($expected_bot_event, $actual_bot_event);
        }

        // make sure the counts are the same
        PHPUnit::assertCount(count($expected_bot_events), $actual_bot_events, "Did not find the correct number of Bot Events");
    }

    protected function validateExpectedBotEvent($expected_bot_event, $actual_bot_event) {
        PHPUnit::assertNotEmpty($actual_bot_event, "Missing bot event ".json_encode($expected_bot_event, 192));
        PHPUnit::assertEquals($expected_bot_event, $actual_bot_event, "ExpectedBotEvent mismatch");
    }




    protected function normalizeExpectedBotEvent($expected_bot_event, $actual_bot_event) {
        $normalized_expected_bot_event = [];

        // placeholder
        $normalized_expected_bot_event = $expected_bot_event;

        ///////////////////
        // EXPECTED
        $expected_fields = ['name','msg',];
        foreach ($expected_fields as $field) {
            $normalized_expected_bot_event[$field] = isset($expected_bot_event[$field]) ? $expected_bot_event[$field] : '[none provided]';
        }
        ///////////////////

        ///////////////////
        // NOT REQUIRED
        $optional_fields = ['txid','file','line',];
        foreach ($optional_fields as $field) {
            if (isset($expected_bot_event[$field])) { $normalized_expected_bot_event[$field] = $expected_bot_event[$field]; }
                else if (isset($actual_bot_event[$field])) { $normalized_expected_bot_event[$field] = $actual_bot_event[$field]; }
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
        $optional_fields = ['id','bot_id','updated_at','created_at',];
        foreach ($optional_fields as $field) {
            if (isset($expected_transaction_model[$field])) { $normalized_expected_transaction_model[$field] = $expected_transaction_model[$field]; }
                else if (isset($actual_transaction_model[$field])) { $normalized_expected_transaction_model[$field] = $actual_transaction_model[$field]; }
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
        // ['id','state','swaps','uuid','user_id','balances','balances_updated_at','blacklist_addresses','status_details','created_at','updated_at',]
        $optional_fields = array_merge(array_keys(app('BotHelper')->sampleBotVars()), ['id','uuid','user_id','created_at','updated_at',]);
        foreach ($optional_fields as $field) {
            if (isset($expected_bot_model[$field])) { $normalized_expected_bot_model[$field] = $expected_bot_model[$field]; }
                else if (isset($actual_bot_model[$field])) { $normalized_expected_bot_model[$field] = $actual_bot_model[$field]; }
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
    // Bots

    protected function addBots($bots) {
        if (!isset($this->bot_models)) { $this->bot_models = []; }
        foreach($bots as $bot_entry) {
            $bot_attributes = $this->loadBaseFilename($bot_entry, "bots");
            unset($bot_entry['meta']);
            $bot_attributes = array_replace_recursive($bot_attributes, $bot_entry);
            $this->bot_models[] = $this->bot_helper->newSampleBot($this->getSampleUser(), $bot_attributes);
        }
        return $this->bot_models;
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
        } else {
            return [];
        }

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

}