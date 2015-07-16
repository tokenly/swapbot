<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class SwapEventstreamAPITest extends TestCase {

    protected $use_database = true;

    public function testSwapEventStreamAPI()
    {
        $sample_bot = app('BotHelper')->newSampleBot();

        // setup the API tester
        $tester = $this->setupAPITester($sample_bot);

        // test public swapevents
        $bot_event_helper = app('BotEventHelper');
        $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot);
        $actual_swaps = app('Swapbot\Repositories\SwapRepository')->findAll();

        $response = $tester->callAPIWithoutAuthentication('GET', '/api/v1/public/swapevents/'.$sample_bot['uuid']);
        $actual_response_from_api = json_decode($response->getContent(), true);
        PHPUnit::assertNotEmpty($actual_response_from_api);
        PHPUnit::assertEquals($actual_swaps[0]['uuid'], $actual_response_from_api[0]['swapUuid']);
        PHPUnit::assertTrue(array_key_exists('quantityIn', $actual_response_from_api[0]['event']));
    }


    public function testRecentSwapEventStreamAPI()
    {
        $sample_bot = app('BotHelper')->newSampleBot();
        $bot_event_helper = app('BotEventHelper');
        $swap_helper = app('SwapHelper');

        // setup the API tester
        $tester = $this->setupAPITester($sample_bot);

        $swap1 = app('SwapHelper')->newSampleSwap($sample_bot);
        $swap2 = app('SwapHelper')->newSampleSwap($sample_bot);
        $swap3 = app('SwapHelper')->newSampleSwap($sample_bot);
        $swap4 = app('SwapHelper')->newSampleSwap($sample_bot);
        $swap5 = app('SwapHelper')->newSampleSwap($sample_bot);

        // create sample events
        $events = [];
        $events[] = $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot, $swap1); usleep(1000);
        $events[] = $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot, $swap1); usleep(1000);
        $events[] = $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot, $swap1); usleep(1000);

        $events[] = $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot, $swap2); usleep(1000);
        $events[] = $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot, $swap3); usleep(1000);

        $events[] = $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot, $swap4); usleep(1000);
        $events[] = $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot, $swap4); usleep(1000);

        // get one per swap 0 by serial asc
        $response_from_api = $tester->callAPIWithoutAuthenticationAndValidateResponse('GET', '/api/v1/public/swapevents/'.$sample_bot['uuid'].'?limit=4&sort=serial asc');
        PHPUnit::assertNotEmpty($response_from_api);
        PHPUnit::assertCount(4, $response_from_api);
        PHPUnit::assertEquals($events[2]['uuid'], $response_from_api[0]['id']);
        PHPUnit::assertEquals($events[3]['uuid'], $response_from_api[1]['id']);

        // get one per swap 0 by serial asc
        $response_from_api = $tester->callAPIWithoutAuthenticationAndValidateResponse('GET', '/api/v1/public/swapevents/'.$sample_bot['uuid'].'?limit=2&sort=serial asc');
        PHPUnit::assertNotEmpty($response_from_api);
        PHPUnit::assertCount(2, $response_from_api);
        PHPUnit::assertEquals($events[2]['uuid'], $response_from_api[0]['id']);
        PHPUnit::assertEquals($events[3]['uuid'], $response_from_api[1]['id']);

        // get one per swap 0 by serial desc
        $response_from_api = $tester->callAPIWithoutAuthenticationAndValidateResponse('GET', '/api/v1/public/swapevents/'.$sample_bot['uuid'].'?limit=2&sort=serial desc');
        PHPUnit::assertNotEmpty($response_from_api);
        PHPUnit::assertCount(2, $response_from_api);
        PHPUnit::assertEquals($events[6]['uuid'], $response_from_api[0]['id']);
        PHPUnit::assertEquals($events[4]['uuid'], $response_from_api[1]['id']);
    }




    public function setupAPITester($sample_bot) {
        $bot_event_helper = app('BotEventHelper');

        $tester = app('APITestHelper');
        $tester
            ->setURLBase('/api/v1/botevents')
            ->useUserHelper(app('UserHelper'))
            ->useRepository(app('Swapbot\Repositories\BotEventRepository'))
            ->createModelWith(function($user) use ($bot_event_helper, $sample_bot) {
                return $bot_event_helper->newSampleSwapEventstreamEvent($sample_bot);
            });

        return $tester;
    }

}
