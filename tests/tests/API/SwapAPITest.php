<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class SwapAPITest extends TestCase {

    protected $use_database = true;

    public function testSwapAPI()
    {
        // setup the API tester
        $tester = $this->setupAPITester();
        // $user = $tester->getUser();

        // test public
        $bot_helper = $this->app->make('BotHelper');
        $user_helper = $this->app->make('UserHelper');
        $user = $user_helper->getSampleUser();
        $bot = $bot_helper->getSampleBot($user);
        echo "\$bot: ".$bot['uuid']."\n";
        
        $tester->testPublicIndex('/api/v1/public/swaps/', $bot['uuid']);
    }


    public function setupAPITester() {
        $bot_helper = $this->app->make('BotHelper');
        $swap_helper = $this->app->make('SwapHelper');

        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/bots')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\SwapRepository'))
            ->createModelWith(function($user) use ($bot_helper, $swap_helper) {
                $bot = $bot_helper->getSampleBot($user);
                return $swap_helper->newSampleSwap($bot);
            });

        return $tester;
    }

}
