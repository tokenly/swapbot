<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotEventsAPITest extends TestCase {

    protected $use_database = true;

    public function testBotEventsAPI()
    {
        $sample_bot = $this->app->make('BotHelper')->newSampleBot();

        // setup the API tester
        $tester = $this->setupAPITester($sample_bot);

        // require user
        $tester->testRequiresUser();
        
        // index
        $tester->testIndex($sample_bot['uuid'], false);

        // test public
        $tester->testPublicIndex('/api/v1/public/botevents/', $sample_bot['uuid'], false);
    }


    public function setupAPITester($sample_bot) {
        $bot_event_helper = $this->app->make('BotEventHelper');

        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/botevents')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\BotEventRepository'))
            ->createModelWith(function($user) use ($bot_event_helper, $sample_bot) {
                return $bot_event_helper->newSampleBotEvent($sample_bot);
            });

        return $tester;
    }

}
