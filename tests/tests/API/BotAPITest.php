<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotAPITest extends TestCase {

    protected $use_database = true;

    public function testBotAPI()
    {
        // mock xchain client so we don't try to make real calls
        $mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        // setup the API tester
        $tester = $this->setupAPITester();

        // require user
        $tester->testRequiresUser();
        
        // index
        $tester->testIndex();
        
        // test create
        $bot_helper = app('BotHelper');
        $tester->testCreate($bot_helper->sampleBotVarsForAPI());

        // test show
        $tester->testShow();

        // test update
        $tester->testUpdate(['name' => 'Updated Name', 'description' => 'Updated description', 'returnFee' => 0.0000123]);

        // test delete
        $tester->testDelete();

        // test public
        $tester->testPublicShow('/api/v1/public/bot/');
    }

    public function testBotBelongsToUser() {
        // create a sample bot with the standard user
        $new_bot = app('BotHelper')->newSampleBot();

        // now create a separate user
        $another_user = app('UserHelper')->getSampleUser('user2@tokenly.co');

        // now call the show method as the other user
        $tester = $this->setupAPITester();
        $tester->be($another_user);
        $response = $tester->callAPIWithAuthentication('GET', '/api/v1/bots/'.$new_bot['uuid']);

        // should be unauthorized
        PHPUnit::assertEquals(403, $response->getStatusCode());
    }

    public function testBotPlansAPI() {
        // mock quotebot
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        // setup the API tester
        $tester = $this->setupAPITester();

        $response = $tester->callAPIWithAuthentication('GET', '/api/v1/plans');
        PHPUnit::assertEquals(200, $response->getStatusCode());
        $actual_response = json_decode($response->getContent(), true);
        PHPUnit::assertEquals('TOKENLY', $actual_response['monthly001']['monthlyRates']['tokenly']['asset']);
    }

    public function testBotImageAPI() {
        $mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        $tester = $this->setupAPITester();

        $image_helper = app('ImageHelper');
        $image_helper->bindMockImageRepository();
        $image = $image_helper->newSampleImage(app('UserHelper')->getSampleUser());

        $bot_helper = app('BotHelper');
        $bot_vars = $bot_helper->sampleBotVarsForAPI();
        $bot_vars['backgroundImageId'] = $image['uuid'];

        $actual_response = $tester->callAPIAndValidateResponse('POST', '/api/v1/bots', $bot_vars);

        PHPUnit::assertEquals($image['uuid'], $actual_response['backgroundImageDetails']['id']);
        PHPUnit::assertEquals('foo.jpg', $actual_response['backgroundImageDetails']['originalFilename']);
    }

    public function testBotUpdateImageAPI() {
        $mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        $tester = $this->setupAPITester();
        $image_helper = app('ImageHelper');
        $image_helper->bindMockImageRepository();
        $bot_helper = app('BotHelper');


        $image = $image_helper->newSampleImage(app('UserHelper')->getSampleUser());

        // add a bot and associate the image
        $bot_vars = $bot_helper->sampleBotVarsForAPI();
        $bot_vars['backgroundImageId'] = $image['uuid'];
        $actual_response = $tester->callAPIAndValidateResponse('POST', '/api/v1/bots', $bot_vars);
        $bot_uuid = $actual_response['id'];


        // now a bot and associate the image
        $image_2 = $image_helper->newSampleImage(app('UserHelper')->getSampleUser());
        $bot_update_vars = ['backgroundImageId' => $image_2['uuid']];
        $tester->callAPIAndValidateResponse('PUT', '/api/v1/bots/'.$bot_uuid, $bot_update_vars, 204);

        // reload the bot
        $bot_repository = app('Swapbot\Repositories\BotRepository');
        $reloaded_bot = $bot_repository->findByUuid($bot_uuid);
        PHPUnit::assertEquals($image_2['id'], $reloaded_bot['background_image_id']);

        // there should only be one image left in the repository
        $all_images = iterator_to_array(app('Swapbot\Repositories\Mock\MockImageRepository')->findAll());
        PHPUnit::assertCount(1, $all_images);
    }

    public function testGetAllBots() {
        $tester = $this->setupAPITester();

        // create a sample bot with the standard user
        $new_bot_1 = app('BotHelper')->newSampleBot();
        $new_bot_2 = app('BotHelper')->newSampleBot();

        // now create a separate user
        $another_user = app('UserHelper')->newRandomUser(['privileges' => ['viewBots' => true]]);
        $new_bot_3 = app('BotHelper')->newSampleBot($another_user);

        // first user is unauthorized
        $loaded_bots = $tester->callAPIAndValidateResponse('GET', '/api/v1/bots', ['allusers' => null], 403);

        // now call the method as the other user
        $tester = $this->setupAPITester();
        $tester->be($another_user);
        $loaded_bots = $tester->callAPIAndValidateResponse('GET', '/api/v1/bots', ['allusers' => null]);

        PHPUnit::assertCount(3, $loaded_bots);
        PHPUnit::assertEquals($new_bot_1['uuid'], $loaded_bots[0]['id']);
    }


    public function setupAPITester() {
        $bot_helper = app('BotHelper');
        $tester = app('APITestHelper');
        $tester
            ->setURLBase('/api/v1/bots')
            ->useUserHelper(app('UserHelper'))
            ->useRepository(app('Swapbot\Repositories\BotRepository'))
            ->createModelWith(function($user) use ($bot_helper) {
                return $bot_helper->newSampleBot($user);
            });

        return $tester;
    }

}
