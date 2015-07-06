<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\Bot;
use Swapbot\Models\Image;
use \PHPUnit_Framework_Assert as PHPUnit;

class ImageAPITest extends TestCase {

    protected $use_database = true;

    public function testImageAPI()
    {
        $image_helper = app('ImageHelper');
        $image_helper->bindMockImageRepository();



        $bot_helper = app('BotHelper');

        // setup the API tester
        $tester = $this->setupAPITester();
        
        // create a new image
        $response = $tester->callAPIAndValidateResponse('POST', "/api/v1/images", ['image' => 'bar3.jpg']);
        // echo "\$response: ".json_encode($response, 192)."\n";

        // test update
        $response = $tester->callAPIAndValidateResponse('POST', "/api/v1/images", ['image' => 'bar4.jpg']);
        // echo "\$response: ".json_encode($response, 192)."\n";

        // // test delete
        // $tester->testDelete();


    }


    public function testValidateImageAPI()
    {
        $image_helper = app('ImageHelper');
        $image_helper->bindMockImageRepository();

        $bot_helper = app('BotHelper');

        // setup the API tester
        $tester = $this->setupAPITester();

        // create a new image
        $response = $tester->callAPIWithAuthentication('POST', "/api/v1/images", ['image' => '']);
        PHPUnit::assertEquals(422, $response->getStatusCode());
    }



    public function setupAPITester() {
        $image_helper = app('ImageHelper');
        $tester = app('APITestHelper');

        // if (!$bot) {
        //     $bot_helper = app('BotHelper');
        //     $bot = $bot_helper->newSampleBot();
        // }

        $tester
            ->setURLBase('/api/v1/images')
            ->useUserHelper(app('UserHelper'))
            ->useRepository(app('Swapbot\Repositories\Mock\MockImageRepository'))
            ->createModelWith(function($setting) use ($image_helper) {
                return $image_helper->newSampleImage();
            });

        return $tester;
    }

}
