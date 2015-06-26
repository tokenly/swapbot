<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\Setting;
use \PHPUnit_Framework_Assert as PHPUnit;

class SettingAPITest extends TestCase {

    protected $use_database = true;

    public function testSettingAPI()
    {
        // setup the API tester
        $tester = $this->setupAPITester();

        // require setting
        $tester->testRequiresUser();
        
        // index
        $tester->testIndex();
        
        // test create
        $setting_helper = $this->app->make('SettingHelper');
        $tester->testCreate($setting_helper->sampleSettingVars(['name' => 'foo1', 'value' => json_encode(['bar1' => 'baz1']), ]));

        // test show
        $tester->testShow();

        // test update
        $tester->testUpdate(['value' => json_encode(['Updated' => 'Value',])]);

        // test delete
        $tester->testDelete();


    }



    public function setupAPITester() {
        $user_helper = $this->app->make('UserHelper');
        $setting_helper = $this->app->make('SettingHelper');
        $tester = $this->app->make('APITestHelper');

        // create a master user with createUser privileges
        $email = 'sample@tokenly.co';
        $api_token = $user_helper->testingTokenFromEmail($email);
        $master_user = $user_helper->newSampleUser([
            'email'      => $email,
            'apitoken'   => $api_token,
            'user_id'    => null,
            'privileges' => ['createUser' => true, 'manageSettings' => true, ],
        ]);


        // use the master user with create user privileges
        $tester->be($master_user);

        $tester
            ->setURLBase('/api/v1/settings')
            ->useRepository($this->app->make('Swapbot\Repositories\SettingRepository'))
            ->createModelWith(function($setting) use ($setting_helper) {
                $random_name = uniqid('name');
                return $setting_helper->newSampleSetting([
                    'name'      => $random_name,
                ]);
            });

        return $tester;
    }

}
