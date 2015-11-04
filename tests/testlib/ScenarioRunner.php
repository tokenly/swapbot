<?php

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Mockery as m;
use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Commands\ShutdownBot;
use Swapbot\Events\CustomerAddedToSwap;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\BotLeaseEntryRepository;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Repositories\TransactionRepository;
use Swapbot\Swap\DateProvider\Facade\DateProvider;
use Swapbot\Swap\Util\RequestIDGenerator;
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

    static $XCHAIN_MOCK_RECORDER = false;
    static $XCHAIN_MOCK_BUILDER = false;

    function __construct(Application $app, BotHelper $bot_helper, UserHelper $user_helper, CustomerHelper $customer_helper, TransactionRepository $transaction_repository, BotLedgerEntryRepository $bot_ledger_entry_repository, BotLeaseEntryRepository $bot_lease_entry_repository, BotEventRepository $bot_event_repository, BotRepository $bot_repository, SwapRepository $swap_repository, BotLedgerEntryHelper $bot_ledger_entry_helper, Repository $cache_store, WhitelistHelper $whitelist_helper) {
        $this->app                         = $app;
        $this->bot_helper                  = $bot_helper;
        $this->user_helper                 = $user_helper;
        $this->customer_helper             = $customer_helper;
        $this->transaction_repository      = $transaction_repository;
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
        $this->bot_event_repository        = $bot_event_repository;
        $this->bot_repository              = $bot_repository;
        $this->swap_repository             = $swap_repository;
        $this->bot_lease_entry_repository  = $bot_lease_entry_repository;
        $this->bot_ledger_entry_helper     = $bot_ledger_entry_helper;
        $this->cache_store                 = $cache_store;
        $this->whitelist_helper            = $whitelist_helper;
    }

    public function init($test_case) {
        if (!isset($this->inited)) {
            $this->inited = true;

            $this->mock_builder = app('Tokenly\XChainClient\Mock\MockBuilder');

            // setup mock xchain (only once)
            if (self::$XCHAIN_MOCK_RECORDER === false) {
                self::$XCHAIN_MOCK_RECORDER = $this->mock_builder->installXChainMockClient($test_case);
                self::$XCHAIN_MOCK_BUILDER  = $this->mock_builder;

                $this->xchain_mock_recorder = self::$XCHAIN_MOCK_RECORDER;
            } else {
                $this->mock_builder = self::$XCHAIN_MOCK_BUILDER;
                $this->xchain_mock_recorder = self::$XCHAIN_MOCK_RECORDER;
                $this->xchain_mock_recorder->calls = [];
            }

            // $this->xchain_mock_recorder = $this->mock_builder->installXChainMockClient($test_case);

            $this->mock_builder->stopThrowingExceptions();
            $this->mock_builder->clearBalances();

            // mock pusher client
            app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($test_case);

            // clear bot events
            $this->clearBotEvents();

            // set mock config
            Config::set('swapbot.xchain_fuel_pool_address_id', 'XCHAIN_FUEL_POOL_ADDRESS_01');
            Config::set('swapbot.xchain_fuel_pool_address',    'swapbotpooladdress0000000000000001');
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
        $this->clearDatabasesForScenario();

        // clear the entire cache for consistency
        $this->cache_store->flush();

        // setup mock quotebot (first)
        $this->quotebot_recorder = $this->installMockQuotebot(isset($scenario_data['quotebot']) ? $scenario_data['quotebot'] : null);

        // setup mock mailer
        $this->mock_mailer_recorder = $this->installMockMailer();

        // set now
        DateProvider::setNow(Carbon::parse('2015-06-01'));

        // set up the scenario
        $whitelists = $this->addWhitelists(isset($scenario_data['whitelists']) ? $scenario_data['whitelists'] : []);
        $bots = $this->addBots(isset($scenario_data['bots']) ? $scenario_data['bots'] : []);

        // set up xchain balances
        $this->setupXChainBalances($scenario_data, isset($scenario_data['bots']) ? $scenario_data['bots'] : []);

        $events = $this->normalizeScenarioEvents($scenario_data);
        foreach($events as $event) {
            $this->executeScenarioEvent($event, $scenario_data);
        }
    }

    public function validateScenario($scenario_data) {
        if (isset($scenario_data['expectedXChainCalls'])) { $this->validateExpectedXChainCalls($scenario_data['expectedXChainCalls'], $scenario_data); }
        if (isset($scenario_data['expectedBotEvents'])) { $this->validateExpectedBotEvents($scenario_data['expectedBotEvents'], $scenario_data); }
        if (isset($scenario_data['expectedTransactionModels'])) { $this->validateExpectedTransactionModels($scenario_data['expectedTransactionModels']); }
        if (isset($scenario_data['expectedBotLedgerEntries'])) { $this->validateExpectedBotLedgerEntryModels($scenario_data['expectedBotLedgerEntries']); }
        if (isset($scenario_data['expectedBotModels'])) { $this->validateExpectedBotModels($scenario_data['expectedBotModels']); }
        if (isset($scenario_data['expectedSwapModels'])) { $this->validateExpectedSwapModels($scenario_data['expectedSwapModels']); }
        if (isset($scenario_data['expectedEmails'])) { $this->validateExpectedEmails($scenario_data['expectedEmails']); }
        if (isset($scenario_data['expectedQuoteClientCalls'])) { $this->validateExpecteQuoteClientCalls($scenario_data['expectedQuoteClientCalls']); }
        if (isset($scenario_data['expectedLeaseEntries'])) { $this->validateExpectedBotLeaseEntryModels($scenario_data['expectedLeaseEntries']); }
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
        if (!$all_xchain_notifications) { $all_xchain_notifications = []; }
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

            // increase the mock account
            if ($notification['event'] == 'receive') {
                // fix blockId and blockhash
                if (isset($notification['blockId']) AND $notification['blockId']) {
                    $notification['bitcoinTx']['blockheight'] = $notification['blockId'];
                }

                $this->mock_builder->receive($notification);
            }

            // ensure a hash for the block event
            if ($notification['event'] == 'block') {
                if (!isset($notification['hash'])) {
                    $notification['hash'] = app('BlockHelper')->sampleBlockHash($notification['height']);
                }
            }


            // look for exceptions trigger
            if (isset($meta['xchainFailAfterRequests'])) {
                $this->mock_builder->beginThrowingExceptionsAfterCount($meta['xchainFailAfterRequests'], isset($meta['xchainFailIgnorePrefixes']) ? $meta['xchainFailIgnorePrefixes'] : $this->standardIgnoreXChainCallPrefixes());
            } else {
                // stop throwing exceptions
                $this->mock_builder->stopThrowingExceptions();
            }

            // process the notification
            $this->dispatch(new ReceiveWebhook($notification));

        }
    }

    protected function executeScenarioEvent_addCustomer($event, $scenario_data) {
        $customer_attributes = array_replace_recursive($this->loadDataByBaseFilename($event['baseFilename'], "customers"), isset($event['data']) ? $event['data'] : []);
        $swap = $this->resolveSwap($event, $scenario_data);

        $customer = $this->customer_helper->newSampleCustomer($swap, $customer_attributes);
        Event::fire(new CustomerAddedToSwap($customer, $swap));
    }

    protected function executeScenarioEvent_setDate($event, $scenario_data) {
        DateProvider::setNow(Carbon::parse($event['date']));
    }

    protected function executeScenarioEvent_changeBot($event, $scenario_data) {
        $update_attributes = array_replace_recursive($this->loadDataByBaseFilename($event['baseFilename'], "bots"), isset($event['data']) ? $event['data'] : []);

        // find the existing bot
        $bot_offset = isset($event['botOffset']) ? $event['botOffset'] : 0;
        $updating_bot = $this->bot_models[$bot_offset];

        // fix swaps
        if (isset($update_attributes['swaps'])) {
            $update_attributes['swaps'] = $updating_bot->unSerializeSwaps($update_attributes['swaps']);
        }

        $this->bot_repository->update($updating_bot, $update_attributes);
        $this->bot_models[$bot_offset] = $updating_bot;
    }

    protected function executeScenarioEvent_shutdownBot($event, $scenario_data) {
        // find the existing bot
        $bot_offset = isset($event['botOffset']) ? $event['botOffset'] : 0;
        $bot = $this->bot_models[$bot_offset];

        // get the shutdown address
        $shutdown_address = isset($event['shutdownAddress']) ? $event['shutdownAddress'] : '1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD';

        // execute the command
        $this->dispatch(new ShutdownBot($bot, $shutdown_address));

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

    protected function resolveSwap($event, $scenario_data) {
        // find the first available swap
        $all_swaps = $this->swap_repository->findAll();
        if (count($all_swaps) > 1) {
            throw new Exception("Multiple swaps found.  Don't know which to attach to.", 1);
        }
        return $all_swaps[0];
    }


    ////////////////////////////////////////////////////////////////////////
    // Whitelists

    protected function addWhitelists($whitelists) {
        if (!isset($this->whitelist_models)) { $this->whitelist_models = []; }
        foreach($whitelists as $whitelist_entry) {
            // $whitelist_attributes = $this->loadBaseFilename($whitelist_entry, "whitelists");
            // unset($whitelist_entry['meta']);
            // $whitelist_attributes = array_replace_recursive($whitelist_attributes, $whitelist_entry);

            $whitelist_attributes = $whitelist_entry;

            $whitelist = $this->whitelist_helper->newSampleWhitelist($this->getSampleUser(), $whitelist_attributes);
            $this->whitelist_models[] = $whitelist;
        }
        return $this->whitelist_models;
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

            $leases = isset($bot_attributes['leases']) ? $bot_attributes['leases'] : null;
            unset($bot_attributes['leases']);

            $bot = $this->bot_helper->newSampleBot($this->getSampleUser(), $bot_attributes);
            $this->bot_models[] = $bot;

            if ($payments) {
                foreach($payments as $payment) {
                    $this->addPayment($bot, $payment);
                }
            }

            if ($leases) {
                foreach($leases as $lease) {
                    $this->addLease($bot, $lease);
                }
            }
        }
        return $this->bot_models;
    }

    protected function addPayment($bot, $payment) {
        $is_credit = isset($payment['credit']) ? $payment['credit'] : true;
        $amount = $payment['amount'];
        $asset = $payment['asset'];

        $bot_event = app('BotEventHelper')->newSampleBotEvent($bot, ['event' => ['name' => 'test.payment.setup', 'msg' => 'scenario starting payment']]);

        if ($is_credit) {
            $this->bot_ledger_entry_repository->addCredit($bot, $amount, $asset, $bot_event);
        } else {
            $this->bot_ledger_entry_repository->addDebit($bot, $amount, $asset, $bot_event);
        }
    }

    protected function addLease($bot, $lease) {
        $bot_event = app('BotEventHelper')->newSampleBotEvent($bot, ['event' => ['name' => 'test.lease.setup', 'msg' => 'scenario starting lease']]);
        $this->bot_lease_entry_repository->addNewLease($bot, $bot_event, Carbon::parse($lease['start_date']), $lease['length']);
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

    protected function standardIgnoreEventPrefixes() {
        return [
            'account.transferIncome',
            'account.transferInventory',
            // 'account.closeSwapAccount',
            'bot.balancesSynced',
            'bot.paymentStateChange',
        ];
    }

    protected function validateExpectedBotEvents($expected_bot_events, $scenario_data) {
        $ignore_event_prefixes = $this->standardIgnoreEventPrefixes();
        if (array_key_exists('ignoreEventPrefixes', $scenario_data)) { $ignore_event_prefixes = $scenario_data['ignoreEventPrefixes']; }
        if ($ignore_event_prefixes === null) { $ignore_event_prefixes = []; }

        $actual_bot_events = [];
        foreach ($this->bot_event_repository->findAll() as $bot_event) {
            $event_vars = $bot_event->toArray()['event'];

            // should ignore?
            $ignore = false;
            foreach($ignore_event_prefixes as $ignore_event_prefix) {
                if (substr($event_vars['name'], 0, strlen($ignore_event_prefix)) == $ignore_event_prefix) {
                    $ignore = true;
                    break;
                }
            }
            if ($ignore) { continue; }

            // ignore the test payment setup
            if ($event_vars['name'] == 'test.payment.setup') { continue; }
            if ($event_vars['name'] == 'test.lease.setup') { continue; }

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

        // regex
        foreach($expected_bot_event as $field => $expected_val) {
            $actual = isset($actual_bot_event[$field]) ? $actual_bot_event[$field] : null;
            $normalized_expected_bot_event[$field] = $this->actualIfRegexMatch($expected_val, $actual);
        }

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

    protected function standardIgnoreXChainCallPrefixes() {
        return [
            '/accounts/transfer/',
            '/accounts/balances/',
        ];
    }

    protected function validateExpectedXChainCalls($expected_xchain_calls, $scenario_data) {
        $actual_xchain_calls = [];

        $ignore_xchain_call_prefixes = $this->standardIgnoreXChainCallPrefixes();
        if (array_key_exists('ignoreXchainCallPrefixes', $scenario_data)) { $ignore_xchain_call_prefixes = $scenario_data['ignoreXchainCallPrefixes']; }
        if ($ignore_xchain_call_prefixes === null) { $ignore_xchain_call_prefixes = []; }


        foreach ($this->xchain_mock_recorder->calls as $call) {
            $ignore = false;
            foreach($ignore_xchain_call_prefixes as $ignore_xchain_call_prefix) {
                if (substr($call['path'], 0, strlen($ignore_xchain_call_prefix)) == $ignore_xchain_call_prefix) {
                    $ignore = true;
                    break;
                }
            }
            if (!$ignore) { $actual_xchain_calls[] = $call; }
        }

        if ($expected_xchain_calls === 'none') {
            $count = count($actual_xchain_calls);
            PHPUnit::assertEmpty($actual_xchain_calls, "Found ".$count." unexpected XChain call".($count==1?'':'s')."\n".json_encode($actual_xchain_calls, 192));
            return;
        }


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

        ///////////////////
        // NOT REQUIRED
        $optional_fields = ['requestId','to','account','unconfirmed',];
        foreach ($optional_fields as $field) {
            if (isset($expected_xchain_call['data'][$field])) {
                $actual = isset($actual_xchain_call['data'][$field]) ? $actual_xchain_call['data'][$field] : null;
                $normalized_expected_xchain_call['data'][$field] = $this->actualIfRegexMatch($expected_xchain_call['data'][$field], $actual);
            }
                else if (isset($actual_xchain_call['data'][$field])) { $normalized_expected_xchain_call['data'][$field] = $actual_xchain_call['data'][$field]; }
        }
        ///////////////////

        ///////////////////
        // Special
        if (isset($expected_xchain_call['data']['requestId']) AND substr($expected_xchain_call['data']['requestId'],0,8) == 'buildFn:' AND $actual_xchain_call) {
            $validate_type = substr($expected_xchain_call['data']['requestId'],8);
            $normalized_expected_xchain_call['data']['requestId'] = $this->{"validateXchainCallRequestId_{$validate_type}"}($actual_xchain_call);
        }
        ///////////////////

        return $normalized_expected_xchain_call;
    }

    protected function validateXchainCallRequestId_swap($actual_xchain_call) {
        return $this->validateXchainCallRequestId('swap', $actual_xchain_call);
    }

    protected function validateXchainCallRequestId_refund($actual_xchain_call) {
        return $this->validateXchainCallRequestId('refund', $actual_xchain_call);
    }

    protected function validateXchainCallRequestId($type, $actual_xchain_call) {
        $actual_request_id = isset($actual_xchain_call['data']['requestId']) ? $actual_xchain_call['data']['requestId'] : null;
        $expected_request_id = 'nope';

        // last bot
        $all_bots = iterator_to_array($this->bot_repository->findAll());
        $bot = $all_bots[count($all_bots) - 1];

        // last swap
        $all_swaps = iterator_to_array($this->swap_repository->findAll());
        $swap = $all_swaps[count($all_swaps) - 1];

        // build expected request id
        $destination = isset($actual_xchain_call['data']['destination']) ? $actual_xchain_call['data']['destination'] : null;
        $quantity = isset($actual_xchain_call['data']['quantity']) ? $actual_xchain_call['data']['quantity'] : null;
        $asset = isset($actual_xchain_call['data']['asset']) ? $actual_xchain_call['data']['asset'] : null;
        $text_to_be_hashed = $type.','.$bot['uuid'].','.$swap['uuid'].','.$destination.','.$quantity.','.$asset;
        $expected_request_id = md5($text_to_be_hashed);
        return $expected_request_id;
    }

    protected function validateXchainCallRequestId_initialFuel($actual_xchain_call) {
        // last bot
        $all_bots = iterator_to_array($this->bot_repository->findAll());
        $bot = $all_bots[count($all_bots) - 1];

        $prefix = 'initialfuel'.','.$bot['uuid'];
        $destination = $actual_xchain_call['data']['destination'];
        $quantity = $actual_xchain_call['data']['quantity'];
        $asset = $actual_xchain_call['data']['asset'];

        return RequestIDGenerator::generateSendHash($prefix, $destination, $quantity, $asset);
    }

    protected function validateXchainCallRequestId_incomeForward($actual_xchain_call) {

        // last bot
        $all_bots = iterator_to_array($this->bot_repository->findAll());
        $bot = $all_bots[count($all_bots) - 1];

        $send_uuid = DateProvider::microtimeNow();

        $prefix = 'incomeforward'.','.$bot['uuid'].','.$send_uuid;
        $destination = $actual_xchain_call['data']['destination'];
        $quantity = $actual_xchain_call['data']['quantity'];
        $asset = $actual_xchain_call['data']['asset'];

        return RequestIDGenerator::generateSendHash($prefix, $destination, $quantity, $asset);
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
        PHPUnit::assertEquals($expected_transaction_model, $actual_transaction_model, "ExpectedTransactionRecord mismatch\nExpected Transaction:".json_encode($expected_transaction_model, 192));
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
        $optional_fields = array_merge(array_keys(app('BotHelper')->sampleBotVars()), ['id','uuid','user_id','created_at','updated_at','last_changed_at','shutdown_block','shutdown_address']);
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
        if (!isset($normalized_expected_email_model['text'])) { $normalized_expected_email_model['text'] = $normalized_expected_email_model['body']; }


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
        $must_match_fields = ['body', 'text',];
        $fields_matched = ['body' => false, 'text' => false, ];
        foreach ($must_match_fields as $field) {
            if (isset($actual_email_model[$field])) {
                $required_text_matches = [];
                if (is_array($normalized_expected_email_model[$field])) {
                    $required_text_matches = $normalized_expected_email_model[$field];
                } else {
                    $required_text_matches = [$normalized_expected_email_model[$field]];
                }

                $all_matched = true;
                foreach($required_text_matches as $required_text_match) {
                    if (stristr($actual_email_model[$field], $required_text_match) === false) {
                        $all_matched = false;
                        break;
                    }
                }


                if ($all_matched) {
                    $normalized_expected_email_model[$field] = $actual_email_model[$field];
                    $fields_matched[$field] = true;
                } else {
                    if (is_array($normalized_expected_email_model[$field])) {
                        $normalized_expected_email_model[$field] = implode(' [AND] ', $normalized_expected_email_model[$field]);
                    }
                }
            }
        }
        if (!$fields_matched['body'] AND !$fields_matched['text'] AND $normalized_expected_email_model['text'] == $normalized_expected_email_model['body']) {
            // normalize the body so only the text shows in the error
            $normalized_expected_email_model['body'] = $actual_email_model['body'];
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
    // ExpectedBotLeaseEntryModels

    protected function validateExpectedBotLeaseEntryModels($expected_bot_lease_entries) {
        $actual_bot_lease_entries = [];
        foreach ($this->bot_lease_entry_repository->findAll() as $bot_lease_entry_model) {
            $actual_bot_lease_entries[] = $bot_lease_entry_model->toArray();
        }

        foreach ($expected_bot_lease_entries as $offset => $raw_expected_bot_lease_entry_model) {
            $actual_bot_lease_entry_model = isset($actual_bot_lease_entries[$offset]) ? $actual_bot_lease_entries[$offset] : null;

            $expected_bot_lease_entry_model = $raw_expected_bot_lease_entry_model;
            unset($expected_bot_lease_entry_model['meta']);
            $expected_bot_lease_entry_model = array_replace_recursive($this->loadBaseFilename($raw_expected_bot_lease_entry_model, "bot_lease_models"), $expected_bot_lease_entry_model);

            $expected_bot_lease_entry_model = $this->normalizeExpectedBotLeaseEntryRecord($expected_bot_lease_entry_model, $actual_bot_lease_entry_model);
            
            $this->validateExpectedBotLeaseEntryRecord($expected_bot_lease_entry_model, $actual_bot_lease_entry_model);
        }

        // make sure the counts are the same
        PHPUnit::assertCount(count($expected_bot_lease_entries), $actual_bot_lease_entries, "Did not find the correct number of BotLeaseEntry models");

    }

    protected function validateExpectedBotLeaseEntryRecord($expected_bot_lease_entry_model, $actual_bot_lease_entry_model) {
        PHPUnit::assertNotEmpty($actual_bot_lease_entry_model, "Missing lease entry model ".json_encode($expected_bot_lease_entry_model, 192));
        PHPUnit::assertEquals($expected_bot_lease_entry_model, $actual_bot_lease_entry_model, "ExpectedBotLeaseEntryRecord mismatch");
    }




    protected function normalizeExpectedBotLeaseEntryRecord($expected_bot_lease_entry_model, $actual_bot_lease_entry_model) {
        $normalized_expected_bot_lease_entry_model = [];

        // placeholder
        $normalized_expected_bot_lease_entry_model = $expected_bot_lease_entry_model;


        // ///////////////////
        // // EXPECTED
        // foreach (['txid','quantity','asset','notifiedAddress','event','network',] as $field) {
        //     if (isset($expected_bot_lease_entry_model[$field])) { $normalized_expected_bot_lease_entry_model[$field] = $expected_bot_lease_entry_model[$field]; }
        // }
        // ///////////////////


        ///////////////////
        // NOT REQUIRED
        $optional_fields = ['id','uuid','user_id','bot_id','created_at',];
        foreach ($optional_fields as $field) {
            if (isset($expected_bot_lease_entry_model[$field])) { $normalized_expected_bot_lease_entry_model[$field] = $expected_bot_lease_entry_model[$field]; }
                else if (isset($actual_bot_lease_entry_model[$field])) { $normalized_expected_bot_lease_entry_model[$field] = $actual_bot_lease_entry_model[$field]; }
        }
        ///////////////////

        ///////////////////
        // JUST NEEDS TO EXIST
        $must_exist_fields = ['bot_event_id',];
        foreach ($must_exist_fields as $field) {
            if (isset($actual_bot_lease_entry_model[$field])) { $normalized_expected_bot_lease_entry_model[$field] = $actual_bot_lease_entry_model[$field]; }
        }
        ///////////////////



        // ///////////////////
        // // Special
        // // build satoshis
        // $normalized_expected_bot_lease_entry_model['quantitySat'] = CurrencyUtil::valueToSatoshis($normalized_expected_bot_lease_entry_model['quantity']);
        // // blockhash
        // if (isset($expected_bot_lease_entry_model['blockhash'])) {
        //     $normalized_expected_bot_lease_entry_model['bitcoinTx']['blockhash'] = $expected_bot_lease_entry_model['blockhash'];
        // }
        // ///////////////////



        return $normalized_expected_bot_lease_entry_model;
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Mock Mailer

    public function installMockMailer() {
        // don't background emails
        Mail::setQueue(Queue::getFacadeRoot()->connection('sync'));

        // mock
        $mock = m::mock('Swift_Mailer');
        $mock->shouldReceive('getTransport')->andReturn($transport = m::mock('Swift_Transport'));
        $transport->shouldReceive('stop');
        app('mailer')->setSwiftMailer($mock);

        // expect
        $mailer_recorder = new \stdClass();
        $mailer_recorder->emails = [];
        $mock
            ->shouldReceive('send')
            ->andReturnUsing(function(\Swift_Message $msg) use ($mailer_recorder) {
                $plain_text_content = '';
                $children = $msg->getChildren();
                if ($children) {
                    foreach($children as $child) {
                        if ($child->getContentType() == 'text/plain') {
                            $plain_text_content = $child->getBody();
                            break;
                        }
                    }
                }

                $email = [
                    'subject' => $msg->getSubject(),
                    'to'      => $msg->getTo(),
                    'from'    => $msg->getFrom(),
                    'body'    => $msg->getBody(),
                    'text'    => $plain_text_content,
                ];
                $mailer_recorder->emails[] = $email;

                // log to mail log
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
    

    protected function validateExpecteQuoteClientCalls($expected_quotebot_client_calls) {
        // normalize
        $normalized_expected_quotebot_client_calls = [];
        foreach($expected_quotebot_client_calls as $expected_quotebot_client_call) {
            if (!isset($expected_quotebot_client_call['method'])) { $expected_quotebot_client_call['method'] = 'loadQuote'; }
            $normalized_expected_quotebot_client_calls[] = $expected_quotebot_client_call;
        }

        // if the number of actual calls is greater, then allow them
        $actual_calls = $this->quotebot_recorder->calls;
        if (($diff = (count($actual_calls) - count($normalized_expected_quotebot_client_calls))) > 0) {
            $count_normalized_expected_quotebot_client_calls = count($normalized_expected_quotebot_client_calls);
            for ($i=0; $i < $diff; $i++) { 
                $offset = $count_normalized_expected_quotebot_client_calls + $i;
                $normalized_expected_quotebot_client_calls[$offset] = $actual_calls[$offset];
            }
        }

        PHPUnit::assertEquals($normalized_expected_quotebot_client_calls, $actual_calls, "QuoteClientCalls mismatch");
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function setupXChainBalances($scenario_data, $bots) {
        if (isset($scenario_data['xchainBalances'])) {
            $balances = $scenario_data['xchainBalances'];
        } else {
            $balances = [
                'default' => [
                    'unconfirmed' => ['BTC' => 0],
                    'confirmed'   => ['BTC' => 10, 'LTBCOIN' => 100000, 'SOUP' => 10000, 'EARLY' => 100],
                    'sending'     => ['BTC' => 0],
                ],
            ];

            // override the balances with the bot entry
            foreach($bots as $bot_entry) {
                $bot_attributes = $this->loadBaseFilename($bot_entry, "bots");
                unset($bot_entry['meta']);
                $bot_attributes = array_replace_recursive($bot_attributes, $bot_entry);

                $bot_balances = isset($bot_attributes['balances']) ? $bot_attributes['balances'] : null;
                if ($bot_balances) {
                    $balances['default']['confirmed'] = [];
                    foreach($bot_balances as $asset => $quantity) {
                        $balances['default']['confirmed'][$asset] = $quantity;
                    }
                }
            }
        }

        $this->mock_builder->setBalances($balances);

        $fuel_balances = [
            'default' => [
                'unconfirmed' => ['BTC' => 0],
                'confirmed'   => ['BTC' => 100,],
                'sending'     => ['BTC' => 0],
            ],
        ];
        $this->mock_builder->setBalances($fuel_balances, 'XCHAIN_FUEL_POOL_ADDRESS_01');
    }

    protected function clearDatabasesForScenario() {
        \Swapbot\Models\Bot::truncate();
        \Swapbot\Models\BotEvent::truncate();
        \Swapbot\Models\BotLeaseEntry::truncate();
        \Swapbot\Models\BotLedgerEntry::truncate();
        \Swapbot\Models\Customer::truncate();
        \Swapbot\Models\Image::truncate();
        \Swapbot\Models\NotificationReceipt::truncate();
        \Swapbot\Models\Setting::truncate();
        \Swapbot\Models\Swap::truncate();
        \Swapbot\Models\Block::truncate();
        \Swapbot\Models\Transaction::truncate();
        \Swapbot\Models\User::truncate();

        return;
    }
    
     protected function actualIfRegexMatch($expected, $actual) {
        if (is_string($expected) AND substr($expected, 0, 1) == '/') {
            if (preg_match($expected, $actual)) {
                return $actual;
            }
        }
        return $expected;
    }

   
    
}