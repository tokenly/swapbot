<?php

use Swapbot\Commands\ReceiveWebhook;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\LaravelEventLog\TestingEventLog;
use \PHPUnit_Framework_Assert as PHPUnit;

class ReceiveWebhookHandlerTest extends TestCase {

    protected $use_database = true;



    public function testWebhookHandlerSavesNotificationReceipts()
    {
        app()->bind('eventlog', function($app) {
            return new TestingEventLog();
        });


        $notification = ['notificationId' => 'test00001', 'event' => 'block'];
        $command = new ReceiveWebhook($notification);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($command);

        // check log
        $logged_events = EventLog::getLoggedEvents();
        PHPUnit::assertNotEmpty($logged_events);
        PHPUnit::assertCount(1, $logged_events);
        PHPUnit::assertEquals('block.received', $logged_events[0]['event']);
        
        // check receipt
        $loaded_receipt = app('Swapbot\Repositories\NotificationReceiptRepository')->findByNotificationUUID('test00001');
        PHPUnit::assertNotEmpty($loaded_receipt);
        PHPUnit::assertEquals('test00001', $loaded_receipt['notification_uuid']);


    }



    public function testWebhookHandlerWithDuplicateNotificationReceipts()
    {
        app()->bind('eventlog', function($app) {
            return new TestingEventLog();
        });

        $notification = ['notificationId' => 'test00001', 'event' => 'block'];
        $command = new ReceiveWebhook($notification);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($command);

        // send a second one
        $notification = ['notificationId' => 'test00001', 'event' => 'block'];
        $command = new ReceiveWebhook($notification);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($command);


        // check log
        $logged_events = EventLog::getLoggedEvents();
        PHPUnit::assertNotEmpty($logged_events);
        PHPUnit::assertCount(2, $logged_events);
        PHPUnit::assertEquals('block.received', $logged_events[0]['event']);
        PHPUnit::assertEquals('notification.duplicate', $logged_events[1]['event']);
        
        // check receipt
        $loaded_receipt = app('Swapbot\Repositories\NotificationReceiptRepository')->findByNotificationUUID('test00001');
        PHPUnit::assertNotEmpty($loaded_receipt);
    }


}
