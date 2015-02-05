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
        
        // index
        $tester->testIndex();
        
        // test create
        $user_helper = $this->app->make('UserHelper');
        $tester->testCreate($user_helper->sampleVars(['email' => $user_helper->randomEmail()]));

        // test show
        $tester->testShow();

        // test update
        $tester->testUpdate(['name' => 'Updated Name',]);

        // test delete
        $tester->testDelete();


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
        // setup user first
        $tester = $this->setupAPITester();

        // create a sample user with the standard user
        $standard_sample_user = $this->app->make('UserHelper')->getSampleUser();

        // now create a separate user
        $another_user = $this->app->make('UserHelper')->getSampleUser('user2@tokenly.co');

        // now call the show method as the other user
        $tester->be($another_user);
        $response = $tester->callAPIWithAuthentication('GET', '/api/v1/users/'.$standard_sample_user['uuid']);

        // should be unauthorized
        PHPUnit::assertEquals(403, $response->getStatusCode());
    }

    public function testNormalUsersCantCreateOtherUsers() {
        $tester = $this->setupAPITester();
        $user_helper = $this->app->make('UserHelper');

        // create a sample user with the standard vars
        $unprivileged_user = $user_helper->getSampleUser('unprivileged@tokenly.co');

        // now try to create another user
        $tester->be($unprivileged_user);
        $create_vars = $user_helper->sampleVars(['email' => $user_helper->randomEmail()]);
        $response = $tester->callAPIWithAuthentication('POST', '/api/v1/users', $create_vars);

        // should be unauthorized
        PHPUnit::assertEquals(403, $response->getStatusCode());
    }

    public function setupAPITester() {
        $user_helper = $this->app->make('UserHelper');
        $tester = $this->app->make('APITestHelper');


        // create a master user with createUser privileges
        $email = 'sample@tokenly.co';
        $api_token = $user_helper->testingTokenFromEmail($email);
        $master_user = $user_helper->newSampleUser([
            'email'      => $email,
            'apitoken'   => $api_token,
            'user_id'    => null,
            'privileges' => ['createUser' => true],
        ]);


        // use the master user with create user privileges
        $tester->be($master_user);

        $tester
            ->setURLBase('/api/v1/users')
            ->useRepository($this->app->make('Swapbot\Repositories\UserRepository'))
            ->createModelWith(function($user) use ($user_helper) {
                $email = $user_helper->randomEmail();
                $api_token = $user_helper->testingTokenFromEmail($email);
                return $user_helper->newSampleUser([
                    'email'      => $email,
                    'apitoken'   => $api_token,
                    'user_id'    => $user['id'],
                    'privileges' => []
                ]);
            })
            ->useCleanupFunction(function($user_repository) use ($master_user) {
                foreach($user_repository->findAll() as $model) {
                    if ($model['id'] != $master_user['id']) {
                        $user_repository->delete($model);
                    }
                }

            });

        return $tester;
    }

}
