<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class UserAPITest extends TestCase {

    protected $use_database = true;

    public function testUserAPI()
    {
        // setup the API tester
        $tester = $this->setupAPITester();

        // require user
        $tester->testRequiresUser();
        
        // // index
        // $tester->testIndex();
        
        // // test create
        // $user_helper = $this->app->make('UserHelper');
        // $tester->testCreate($user_helper->sampleUserVars());

        // test show
        $tester->testShow();

        // // test update
        // $tester->testUpdate(['name' => 'Updated Name', 'description' => 'Updated description']);

        // // test delete
        // $tester->testDelete();


    }

    public function testSelfUserAPI()
    {
        // setup the API tester
        $tester = $this->setupAPITester();

        // special test for "me"
        $actual_result = json_decode($tester->callAPIWithAuthentication('GET', 'api/v1/users/me')->getContent(), true);
        PHPUnit::assertArrayNotHasKey('errors', $actual_result, "Errors: ".(isset($actual_result['errors']) ? json_encode($actual_result['errors'], 192) : null));
        $expected_result = $tester->getUser()->serializeForAPI();
        PHPUnit::assertEquals($expected_result, $actual_result);
    }

    public function testUserBelongsToUser() {
        // create a sample user with the standard user
        $new_user = $this->app->make('UserHelper')->getSampleUser();

        // now create a separate user
        $another_user = $this->app->make('UserHelper')->getSampleUser('user2@tokenly.co');

        // now call the show method as the other user
        $tester = $this->setupAPITester();
        $tester->be($another_user);
        $response = $tester->callAPIWithAuthentication('GET', '/api/v1/users/'.$new_user['uuid']);

        // should be unauthorized
        PHPUnit::assertEquals(403, $response->getStatusCode());
    }

    public function setupAPITester() {
        $user_helper = $this->app->make('UserHelper');
        $tester = $this->app->make('APITestHelper');
        $tester
            ->setURLBase('/api/v1/users')
            ->useUserHelper($this->app->make('UserHelper'))
            ->useRepository($this->app->make('Swapbot\Repositories\UserRepository'))
            ->createModelWith(function($user) use ($user_helper) {
                $email = 'u'.md5(uniqid('', true)).'@tokenly.co';
                return $user_helper->getSampleUser($email, null, $user['id']);
            });

        return $tester;
    }

}
