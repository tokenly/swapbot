<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class PaymentAccountAPITest extends TestCase {

    protected $use_database = true;

    public function testIndexPaymentAccountAPI()
    {
        $sample_bot = $this->app->make('BotHelper')->newSampleBot();

        // setup the API tester
        $tester = $this->setupAPITester($sample_bot);

        // require user
        $tester->testRequiresUser('/all');
        
        // index
        $tester->cleanup();

        // create 2 models
        $created_models = [];
        $created_models[] = $tester->newModel();
        $created_models[] = $tester->newModel();
        

        // now call the API
        $response = $tester->callAPIWithAuthentication('GET', '/api/v1/payments/'.$sample_bot['uuid'].'/all');
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".'/api/payments/'.$sample_bot['uuid'].'/all');
        $actual_response_from_api = json_decode($response->getContent(), true);

        // populate the $expected_created_resource
        $expected_api_response = [$created_models[0]->serializeForAPI(), $created_models[1]->serializeForAPI()];
        $expected_api_response[0]['msg'] = 'test bot event';
        $expected_api_response[1]['msg'] = 'test bot event';

        // check response
        PHPUnit::assertEquals($expected_api_response, $actual_response_from_api);

        // return the models
        return $created_models;


    }

    public function testBalancePaymentAccountAPI()
    {
        $sample_bot = $this->app->make('BotHelper')->newSampleBot();

        // setup the API tester
        $tester = $this->setupAPITester($sample_bot);

        // require user authentication
        $tester->testRequiresUser('/balance');
        $tester->cleanup();
        
        // manually add a couple of balance entries
        $repo = app('Swapbot\Repositories\BotLedgerEntryRepository');
        $repo->addCredit($sample_bot, 1600, app('BotEventHelper')->newSampleBotEvent($sample_bot));
        $repo->addDebit($sample_bot, 200, app('BotEventHelper')->newSampleBotEvent($sample_bot));

        // get the balance
        $response = $tester->callAPIWithAuthentication('GET', '/api/v1/payments/'.$sample_bot['uuid'].'/balance');
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$sample_bot['uuid'].'/balance');
        $actual_response_from_api = json_decode($response->getContent(), true);
        PHPUnit::assertEquals(1400, $actual_response_from_api['balance']);

    }


    public function setupAPITester($sample_bot) {
        $bot_ledger_entry_helper = $this->app->make('BotLedgerEntryHelper');

        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/payments')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\BotLedgerEntryRepository'))
            ->createModelWith(function($user) use ($bot_ledger_entry_helper, $sample_bot) {
                return $bot_ledger_entry_helper->newSampleBotLedgerEntry($sample_bot);
            });

        return $tester;
    }

}
