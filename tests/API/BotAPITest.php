<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotAPITest extends TestCase {

    protected $use_database = true;

    public function testAPIGetBotControllerRequiresUser()
    {
        $this->markTestIncomplete();
        // $response = $this->call('GET', '/bot/edit');
        // PHPUnit::assertEquals(302, $response->getStatusCode());
    }

    public function testBotAPI()
    {

        $bot_helper = $this->app->make('BotHelper');
        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/bots')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\BotRepository'))
            ->createModelWith(function($user) use ($bot_helper) {
                return $bot_helper->newSampleBot($user);
            });

        // index
        $tester->testIndex();
        
        // test create
        $tester->testCreate($bot_helper->sampleBotVars());
    }

    public function testBotBelongsToUser() {
        $this->markTestIncomplete();
        $this->setUpDb();

        // create a sample bot with the standard user
        $new_bot = $this->app->make('BotHelper')->newSampleBot();

        // now create a separate user
        $another_user = $this->app->make('UserHelper')->getSampleUser('user2@tokenly.co');

        $this->be($another_user);
        $response = $this->call('GET', '/bot/show/'.$new_bot['uuid']);

        // should be unauthorized
        PHPUnit::assertEquals(403, $response->getStatusCode());

    }

}
