<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class BalanceRefreshAPITest extends TestCase {

    protected $use_database = true;

    public function testBalanceRefreshAPI()
    {
        // install pusher mock
        app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($this);

        $sample_bot = $this->app->make('BotHelper')->newSampleBot(null, ['address' => '1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx']);

        // setup the API tester
        $tester = $this->setupAPITester($sample_bot);

        // require user
        $url = '/api/v1/balancerefresh/'.$sample_bot['uuid'];
        $request = $tester->createAPIRequest('POST', $url);
        $response = $tester->sendRequest($request);
        PHPUnit::assertEquals(403, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

        // setup xchain mocks
        $mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        // refresh balances
        $url = '/api/v1/balancerefresh/'.$sample_bot['uuid'];
        $response = $tester->callAPIWithAuthentication('POST', $url);
        PHPUnit::assertEquals(204, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor POST ".$url);

        // check the xchain calls
        $calls = $mock->calls;
        PHPUnit::assertNotEmpty($calls);
        PHPUnit::assertCount(1, $calls);
        PHPUnit::assertEquals('/balances/1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', $calls[0]['path']);

        // make sure balances_updated_at was changed
        // reload the bot
        $reloaded_bot = $this->app->make('Swapbot\Repositories\BotRepository')->findByID($sample_bot['id']);
        PHPUnit::assertGreaterThan(time() - 5, $reloaded_bot['balances_updated_at']->timestamp);
        PHPUnit::assertLessThanOrEqual(time(), $reloaded_bot['balances_updated_at']->timestamp);
    }


    public function setupAPITester($sample_bot) {
        $bot_event_helper = $this->app->make('BotEventHelper');

        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/balancerefresh')
            ->useUserHelper($this->app->make('UserHelper'));

        return $tester;
    }

}
