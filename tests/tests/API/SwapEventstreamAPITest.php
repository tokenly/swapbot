<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class SwapEventstreamAPITest extends TestCase {

    protected $use_database = true;

    public function testSwapEventStreamAPI()
    {
        $sample_bot = $this->app->make('BotHelper')->newSampleBot();

        // setup the API tester
        $tester = $this->setupAPITester($sample_bot);

        // test public
        $tester->testPublicIndex('/api/v1/public/swapevents/', $sample_bot['uuid'], true);

        // check the stream
        $actual_swaps = app('Swapbot\Repositories\SwapRepository')->findAll();

        $response = $tester->callAPIWithoutAuthentication('GET', '/api/v1/public/swapevents/'.$sample_bot['uuid']);
        $actual_response_from_api = json_decode($response->getContent(), true);
        PHPUnit::assertNotEmpty($actual_response_from_api);
        PHPUnit::assertEquals($actual_swaps[0]['uuid'], $actual_response_from_api[0]['swapUuid']);
    }


    public function setupAPITester($sample_bot) {
        $bot_event_helper = $this->app->make('BotEventHelper');

        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/botevents')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\BotEventRepository'))
            ->createModelWith(function($user) use ($bot_event_helper, $sample_bot) {
                return $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot);
            });

        return $tester;
    }

}
