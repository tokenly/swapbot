<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class WhitelistAPITest extends TestCase {

    protected $use_database = true;

    public function testWhitelistAPI() {
        $whitelist_helper = app('WhitelistHelper');

        $tester = $this->setupAPITester();

        // require user
        $tester->testRequiresUser();
        
        // index
        $tester->testIndex();
        
        // test create
        $tester->testCreate($whitelist_helper->sampleWhitelistVars());

        // test show
        $tester->testShow();

        // test update
        $tester->testUpdate(['name' => 'Updated Name',]);

        // test delete
        $tester->testDelete();
    }

    public function testWhitelistNamesAPI() {
        $whitelist_helper = app('WhitelistHelper');

        $tester = $this->setupAPITester();

        $whitelist_helper->newSampleWhitelist($tester->getUser());
        $whitelist_helper->newSampleWhitelist($tester->getUser(), ['name' => 'My Whitelist Two']);

        $loaded_whitelists = $tester->callAPIAndValidateResponse('GET', '/api/v1/whitelists', ['select' => 'name']);

        PHPUnit::assertCount(2, $loaded_whitelists);
        PHPUnit::assertArrayNotHasKey('data', $loaded_whitelists[0]);
        PHPUnit::assertEquals('My Whitelist', $loaded_whitelists[0]['name']);
        PHPUnit::assertEquals('My Whitelist Two', $loaded_whitelists[1]['name']);

    }

    public function testDeletingWhitelistUpdatesBots() {
        $bot_repository = app('Swapbot\Repositories\BotRepository');
        $tester = $this->setupAPITester();

        $user        = $tester->getUser();
        $whitelist   = app('WhitelistHelper')->newSampleWhitelist($user);
        $bot         = app('BotHelper')->newSampleBotWithUniqueSlug($user, ['whitelist_uuid' => $whitelist['uuid']]);


        // bot has whitelist applied
        $loaded_bot = $bot_repository->findById($bot['id']);
        PHPUnit::assertEquals($whitelist['uuid'], $loaded_bot['whitelist_uuid']);

        // now delete the whitelist
        $tester->callAPIAndValidateResponse('DELETE', '/api/v1/whitelists/'.$whitelist['uuid'], [], 204);

        // whitelist uuid should be gone
        $loaded_bot = $bot_repository->findById($bot['id']);
        PHPUnit::assertEmpty($loaded_bot['whitelist_uuid']);

    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    
    

    public function setupAPITester() {
        $whitelist_helper = app('WhitelistHelper');

        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/whitelists')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\WhitelistRepository'))
            ->createModelWith(function($user) use ($whitelist_helper) {
                return $whitelist_helper->newSampleWhitelist($user);
            });

        return $tester;
    }

}
