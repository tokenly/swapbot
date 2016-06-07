<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Events\SettingWasChanged;
use Swapbot\Models\Setting;
use \PHPUnit_Framework_Assert as PHPUnit;

class GlobalAlertNotificationTest extends TestCase {

    protected $use_database = true;

    public function testGlobalAlertNotifications()
    {
        $notifications = app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($this);
        $setting = $setting_helper = app('SettingHelper')->newSampleSetting([
            'name' => 'globalAlert',
            'value' => [
                'status'  => true,
                'content' => 'hello world',
            ],
        ]);
        Event::fire(new SettingWasChanged($setting, 'create'));

        // create
        PHPUnit::assertCount(1, $notifications->getAllNotifications());
        PHPUnit::assertEquals(true, array_get($notifications->getNotification(0), 'data.status'));
        PHPUnit::assertEquals('hello world', array_get($notifications->getNotification(0), 'data.content'));
        PHPUnit::assertEquals('global', array_get($notifications->getNotification(0), 'data.alertType'));
        $notifications->reset();

        // update
        $setting['value'] = [
            'status'  => false,
            'content' => 'hello world',
        ];
        Event::fire(new SettingWasChanged($setting, 'update'));
        PHPUnit::assertCount(1, $notifications->getAllNotifications());
        PHPUnit::assertEquals(false, array_get($notifications->getNotification(0), 'data.status'));
        PHPUnit::assertEquals('', array_get($notifications->getNotification(0), 'data.content'));
        $notifications->reset();

        // delete
        $setting['value'] = [
            'status'  => true,
            'content' => 'hello world',
        ];
        Event::fire(new SettingWasChanged($setting, 'delete'));
        PHPUnit::assertCount(1, $notifications->getAllNotifications());
        PHPUnit::assertEquals(false, array_get($notifications->getNotification(0), 'data.status'));
        PHPUnit::assertEquals('', array_get($notifications->getNotification(0), 'data.content'));
        $notifications->reset();
    }

}
