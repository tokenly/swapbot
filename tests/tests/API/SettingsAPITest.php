<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
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
        $setting_helper = app('SettingHelper');
        $tester->testCreate($setting_helper->sampleSettingVars(['name' => 'foo1', 'value' => json_encode(['bar1' => 'baz1']), ]));

        // test show
        $tester->testShow();

        // test update
        $tester->testUpdate(['value' => json_encode(['Updated' => 'Value',])]);

        // test delete
        $tester->testDelete();
    }

    public function testSettingAPIPrivilegesForUnauthedUser()
    {
        // setup the API tester
        $tester = $this->setupAPITester();

        // plain user
        $user_helper = app('UserHelper');;
        $plain_user = $user_helper->newRandomUser(['privileges' => [],]);
        $tester->be($plain_user);

        // test create
        $setting_helper = app('SettingHelper');
        $vars = $setting_helper->sampleSettingVars(['name' => 'foo1', 'value' => json_encode(['bar1' => 'baz1']), ]);
        $tester->callAPIAndValidateResponse('POST', '/api/v1/settings', $vars, 403);

        $random_setting = $setting_helper->newSampleSetting([
            'name' => uniqid('name'),
        ]);

        // test update
        $vars = ['value' => json_encode(['Updated' => 'Value',])];
        $tester->callAPIAndValidateResponse('PUT', '/api/v1/settings/'.$random_setting['uuid'], $vars, 403);

        // test delete
        $tester->callAPIAndValidateResponse('DELETE', '/api/v1/settings/'.$random_setting['uuid'], $vars, 403);
    }


    public function testGlobalAlertSettingAPIPrivileges()
    {
        // listen to events
        $events_fired = [];
        Event::listen(Swapbot\Events\SettingWasChanged::class, function($event) use (&$events_fired) {
            $events_fired[] = $event;
        });

        // setup the API tester
        $tester = $this->setupAPITester();

        // plain user
        $user_helper = app('UserHelper');;
        $authed_user = $user_helper->newRandomUser(['privileges' => ['manageGlobalAlert' => true,],]);
        $tester->be($authed_user);

        // test create
        $setting_helper = app('SettingHelper');
        $vars = $setting_helper->sampleSettingVars(['name' => 'globalAlert', 'value' => json_encode(['content' => 'hi', 'status' => true]), ]);
        $response = $tester->callAPIAndValidateResponse('POST', '/api/v1/settings', $vars, 200);
        $global_alert_setting = app('Swapbot\Repositories\SettingRepository')->findByUuid($response['id']);

        // validate event
        $this->validateAndClearEventsFired($events_fired, 'create');

        $vars = $setting_helper->sampleSettingVars(['name' => 'foo', 'value' => json_encode(['content' => 'hi2', 'status' => true]), ]);
        $response = $tester->callAPIAndValidateResponse('POST', '/api/v1/settings', $vars, 403);

        // test update
        $vars = ['value' => json_encode(['Updated' => 'Value',])];
        $tester->callAPIAndValidateResponse('PUT', '/api/v1/settings/'.$global_alert_setting['uuid'], $vars, 204);

        // validate event
        $this->validateAndClearEventsFired($events_fired, 'update');


        // test delete
        $tester->callAPIAndValidateResponse('DELETE', '/api/v1/settings/'.$global_alert_setting['uuid'], $vars, 204);

        // validate event
        $this->validateAndClearEventsFired($events_fired, 'delete');
    }

    public function testGetGlobalAlertAPI() {
        // setup the API tester
        $tester = $this->setupAPITester();


        // get the default global alert
        $response = $tester->callAPIAndValidateResponse('GET', '/api/v1/globalalert', [], 200);
        PHPUnit::assertNotEmpty($response);
        PHPUnit::assertEquals('', $response['content']);
        PHPUnit::assertEquals(false, $response['status']);

        $setting = $setting_helper = app('SettingHelper')->newSampleSetting([
            'name' => 'globalAlert',
            'value' => [
                'status'  => true,
                'content' => 'hello world',
            ],
        ]);

        // get the global alert
        $response = $tester->callAPIAndValidateResponse('GET', '/api/v1/globalalert', [], 200);
        PHPUnit::assertNotEmpty($response);
        PHPUnit::assertEquals('hello world', $response['content']);
        PHPUnit::assertEquals(true, $response['status']);

    }


    public function setupAPITester() {
        $user_helper = app('UserHelper');
        $setting_helper = app('SettingHelper');
        $tester = app('APITestHelper');

        // create a master user with createUser privileges
        $email = 'sample@tokenly.co';
        $api_token = $user_helper->testingTokenFromEmail($email);
        $master_user = $user_helper->newSampleUser([
            'email'      => $email,
            'apitoken'   => $api_token,
            'user_id'    => null,
            'privileges' => ['createUser' => true, 'manageSettings' => true, 'manageBots' => true, ],
        ]);

        // use the master user with create user privileges
        $tester->be($master_user);

        $tester
            ->setURLBase('/api/v1/settings')
            ->useRepository(app('Swapbot\Repositories\SettingRepository'))
            ->createModelWith(function($setting) use ($setting_helper) {
                $random_name = uniqid('name');
                return $setting_helper->newSampleSetting([
                    'name'      => $random_name,
                ]);
            });

        return $tester;
    }

    protected function validateAndClearEventsFired(&$events_fired, $expected_event_type) {
        $this->validateEventsFired($events_fired, $expected_event_type);
        $events_fired = [];
    }

    protected function validateEventsFired($events_fired, $expected_event_type) {
        PHPUnit::assertCount(1, $events_fired);
        PHPUnit::assertEquals($expected_event_type, $events_fired[0]->event_type, "Unepxected event type of ".$events_fired[0]->event_type);
        PHPUnit::assertNotEmpty($events_fired[0]->setting);
    }

}
