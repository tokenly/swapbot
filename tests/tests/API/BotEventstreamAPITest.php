<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotEventstreamAPITest extends TestCase {

    protected $use_database = true;

    public function testBotEventStreamAPI()
    {
        $sample_bot = app('BotHelper')->newSampleBot();

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

    public function testRecentBotEventStreamAPI()
    {
        $sample_bot = app('BotHelper')->newSampleBot();

        // setup the API tester
        $tester = $this->setupAPITester($sample_bot);

        // create sample events
        $bot_event_helper = app('BotEventHelper');
        $events = [];

        $events[] = $bot_event_helper->newSampleBotEventstreamEvent($sample_bot); usleep(1000);
        $events[] = $bot_event_helper->newSampleBotEventstreamEvent($sample_bot); usleep(1000);
        $events[] = $bot_event_helper->newSampleBotEventstreamEvent($sample_bot); usleep(1000);
        $events[] = $bot_event_helper->newSampleBotEventstreamEvent($sample_bot); usleep(1000);

        // get all
        $response_from_api = $tester->callAPIWithoutAuthenticationAndValidateResponse('GET', '/api/v1/public/boteventstream/'.$sample_bot['uuid']);
        PHPUnit::assertNotEmpty($response_from_api);
        PHPUnit::assertCount(4, $response_from_api);
        PHPUnit::assertEquals($events[0]['uuid'], $response_from_api[0]['id']);

        // now get the most recent 2
        $response_from_api = $tester->callAPIWithoutAuthenticationAndValidateResponse('GET', '/api/v1/public/boteventstream/'.$sample_bot['uuid'], ['limit' => 2, 'sort' => 'serial desc']);
        PHPUnit::assertNotEmpty($response_from_api);
        PHPUnit::assertCount(2, $response_from_api);
        PHPUnit::assertEquals($events[3]['uuid'], $response_from_api[0]['id']);
        PHPUnit::assertEquals($events[2]['uuid'], $response_from_api[1]['id']);

        // now get the oldest 1
        $response_from_api = $tester->callAPIWithoutAuthenticationAndValidateResponse('GET', '/api/v1/public/boteventstream/'.$sample_bot['uuid'], ['limit' => 1, 'sort' => 'serial asc']);
        PHPUnit::assertNotEmpty($response_from_api);
        PHPUnit::assertCount(1, $response_from_api);
        PHPUnit::assertEquals($events[0]['uuid'], $response_from_api[0]['id']);
    }


    public function setupAPITester($sample_bot) {
        $bot_event_helper = app('BotEventHelper');

        $tester = app('APITestHelper');
        $tester
            ->setURLBase('/api/v1/botevents')
            ->useUserHelper(app('UserHelper'))
            ->useRepository(app('Swapbot\Repositories\BotEventRepository'))
            ->createModelWith(function($user) use ($bot_event_helper, $sample_bot) {
                return $bot_event_helper->newSampleBotEventstreamEvent($sample_bot);
            });

        return $tester;
    }

}
