<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotAPITest extends TestCase {

    protected $use_database = true;

    public function testBotAPI()
    {
        $tester = $this->setupAPITester();

        // require user
        $tester->testRequiresUser();
        
        // index
        $tester->testIndex();
        
        // test create
        $bot_helper = $this->app->make('BotHelper');
        $tester->testCreate($bot_helper->sampleBotVars());

        // test show
        $tester->testShow();

        // test update
        $tester->testUpdate(['name' => 'Updated Name', 'description' => 'Updated description']);

        // test delete
        $tester->testDelete();
    }

    public function testBotBelongsToUser() {
        // create a sample bot with the standard user
        $new_bot = $this->app->make('BotHelper')->newSampleBot();

        // now create a separate user
        $another_user = $this->app->make('UserHelper')->getSampleUser('user2@tokenly.co');

        // now call the show method as the other user
        $tester = $this->setupAPITester();
        $tester->be($another_user);
        $response = $tester->callAPIWithAuthentication('GET', '/api/v1/bots/'.$new_bot['uuid']);

        // should be unauthorized
        PHPUnit::assertEquals(403, $response->getStatusCode());
    }

    public function setupAPITester() {
        $bot_helper = $this->app->make('BotHelper');
        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/bots')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\BotRepository'))
            ->createModelWith(function($user) use ($bot_helper) {
                return $bot_helper->newSampleBot($user);
            });

        return $tester;
    }

}
