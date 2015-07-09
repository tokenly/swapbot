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

        // test public
        $bot_helper = $this->app->make('BotHelper');
        $user_helper = $this->app->make('UserHelper');
        $user = $user_helper->getSampleUser();
        $bot = $bot_helper->getSampleBot($user);
        
        $tester->testPublicIndex('/api/v1/public/swaps/', $bot['uuid']);
    }

    public function testGetSwapAPI() {
        $tester = $this->setupAPITester();

        $user_1 = app('UserHelper')->newRandomUser();
        $bot_1 = app('BotHelper')->newSampleBot($user_1);
        $new_swap_1 = app('SwapHelper')->newSampleSwap($bot_1);

        // test get swap as admin
        $admin_user = app('UserHelper')->newRandomUser(['privileges' => ['viewSwaps' => true]]);
        $tester->be($admin_user);
        $loaded_swap = $tester->callAPIAndValidateResponse('GET', '/api/v1/swaps/'.$new_swap_1['uuid']);
        PHPUnit::assertNotEmpty($loaded_swap);
        PHPUnit::assertEquals($new_swap_1['uuid'], $loaded_swap['id']);
        PHPUnit::assertEquals($bot_1['uuid'], $loaded_swap['botUuid']);

    }

    public function testGetAllSwaps() {
        $tester = $this->setupAPITester();

        $user_1 = app('UserHelper')->newRandomUser();
        $bot_1 = app('BotHelper')->newSampleBot($user_1);

        $user_2 = app('UserHelper')->newRandomUser();
        $bot_2 = app('BotHelper')->newSampleBot($user_2);

        $admin_user = app('UserHelper')->newRandomUser(['privileges' => ['viewSwaps' => true]]);

        // create a sample bot with the standard user
        $new_swap_1 = app('SwapHelper')->newSampleSwap($bot_1);
        $new_swap_2 = app('SwapHelper')->newSampleSwap($bot_1);
        $new_swap_3 = app('SwapHelper')->newSampleSwap($bot_2);

        // first user is unauthorized
        $loaded_bots = $tester->callAPIAndValidateResponse('GET', '/api/v1/swaps', [], 403);

        // now call the method as the other user
        $tester = $this->setupAPITester();
        $tester->be($admin_user);
        $loaded_swaps = $tester->callAPIAndValidateResponse('GET', '/api/v1/swaps');

        PHPUnit::assertCount(3, $loaded_swaps);
        PHPUnit::assertEquals($new_swap_1['uuid'], $loaded_swaps[0]['id']);
        PHPUnit::assertEquals($bot_1['uuid'], $loaded_swaps[0]['botUuid']);
        PHPUnit::assertEquals($bot_1['name'], $loaded_swaps[0]['botName']);
    }

    public function testGetSwapsWithFilters() {
        $user_1 = app('UserHelper')->newRandomUser();
        $bot_1 = app('BotHelper')->newSampleBot($user_1);

        $user_2 = app('UserHelper')->newRandomUser();
        $bot_2 = app('BotHelper')->newSampleBot($user_2);

        $admin_user = app('UserHelper')->newRandomUser(['privileges' => ['viewSwaps' => true]]);

        // create a sample bot with the standard user
        $new_swap_1 = app('SwapHelper')->newSampleSwap($bot_1, null, ['state' => 'brandnew']);
        $new_swap_2 = app('SwapHelper')->newSampleSwap($bot_1, null, ['state' => 'complete']);
        $new_swap_3 = app('SwapHelper')->newSampleSwap($bot_2, null, ['state' => 'complete']);
        $new_swap_4 = app('SwapHelper')->newSampleSwap($bot_2, null, ['state' => 'error']);

        // now call the method as the other user
        $tester = $this->setupAPITester();
        $tester->be($admin_user);

        // brand new
        $loaded_swaps = $tester->callAPIAndValidateResponse('GET', '/api/v1/swaps', ['state' => 'brandnew']);
        PHPUnit::assertCount(1, $loaded_swaps);
        PHPUnit::assertEquals($new_swap_1['uuid'], $loaded_swaps[0]['id']);

        // complete
        $loaded_swaps = $tester->callAPIAndValidateResponse('GET', '/api/v1/swaps', ['state' => 'complete']);
        PHPUnit::assertCount(2, $loaded_swaps);
        PHPUnit::assertEquals($new_swap_2['uuid'], $loaded_swaps[0]['id']);
        PHPUnit::assertEquals($new_swap_3['uuid'], $loaded_swaps[1]['id']);

    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    
    

    public function setupAPITester() {
        $bot_helper = $this->app->make('BotHelper');
        $swap_helper = $this->app->make('SwapHelper');

        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/swaps')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\SwapRepository'))
            ->createModelWith(function($user) use ($bot_helper, $swap_helper) {
                $bot = $bot_helper->getSampleBot($user);
                return $swap_helper->newSampleSwap($bot);
            });

        return $tester;
    }

}
