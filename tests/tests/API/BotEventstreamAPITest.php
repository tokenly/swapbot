<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotEventstreamAPITest extends TestCase {

    protected $use_database = true;

    public function testBotEventStreamAPI()
    {
        $sample_bot = $this->app->make('BotHelper')->newSampleBot();

        // setup the API tester
        $tester = $this->setupAPITester($sample_bot);

        // test public
        $tester->testPublicIndex('/api/v1/public/boteventstream/', $sample_bot['uuid'], true);

        // check the stream
        $actual_events = app('Swapbot\Repositories\BotEventRepository')->findAll();

        $response = $tester->callAPIWithoutAuthentication('GET', '/api/v1/public/boteventstream/'.$sample_bot['uuid']);
        $response_from_api = json_decode($response->getContent(), true);
        PHPUnit::assertNotEmpty($response_from_api);
        PHPUnit::assertEquals($actual_events[0]['uuid'], $response_from_api[0]['id']);
    }


    public function setupAPITester($sample_bot) {
        $bot_event_helper = $this->app->make('BotEventHelper');

        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/botevents')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\BotEventRepository'))
            ->createModelWith(function($user) use ($bot_event_helper, $sample_bot) {
                return $bot_event_helper->newSampleBotEventstreamEvent($sample_bot);
            });

        return $tester;
    }

}
