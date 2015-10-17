<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Database\QueryException;
use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Repositories\NotificationReceiptRepository;
use Swapbot\Swap\Processor\BlockEventProcessor;
use Swapbot\Swap\Processor\InvalidationEventProcessor;
use Swapbot\Swap\Processor\ReceiveEventProcessor;
use Swapbot\Swap\Processor\SendEventProcessor;
use Tokenly\LaravelEventLog\Facade\EventLog;

class ReceiveWebhookHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(ReceiveEventProcessor $receive_event_processor, SendEventProcessor $send_event_processor, InvalidationEventProcessor $invalidation_event_processor, BlockEventProcessor $block_event_processor, NotificationReceiptRepository $notification_receipt_repository)
    {
        $this->receive_event_processor         = $receive_event_processor;
        $this->send_event_processor            = $send_event_processor;
        $this->invalidation_event_processor    = $invalidation_event_processor;
        $this->block_event_processor           = $block_event_processor;
        $this->notification_receipt_repository = $notification_receipt_repository;
    }

    /**
     * Handle the command.
     *
     * @param  ReceiveWebhook  $command
     * @return void
     */
    public function handle(ReceiveWebhook $command)
    {
        $payload = $command->payload;

        // sometimes swapbot may take a long time to process a notification
        //   and xchain may try to notify us again while we are still processing that notification
        //   to prevent this, we will immediately mark the notification as received and return if it was already processed
        if ($this->checkOrCreateNotificationReceipt($payload)) {
            // event already processed
            return;
        }

        switch ($payload['event']) {
            case 'block':
                // new block event
                EventLog::log('block.received', $payload);
                $this->block_event_processor->handleBlock($payload);
                break;

            case 'receive':
                // new receive event
                EventLog::log('event.receive', $payload);
                $this->receive_event_processor->handleReceive($payload);
                break;

            case 'send':
                // new send event
                EventLog::log('event.send', $payload);
                $this->send_event_processor->handleSend($payload);
                break;

            case 'invalidation':
                // new invalidation event
                // EventLog::log('event.invalidation', $payload);
                $this->invalidation_event_processor->handleInvalidation($payload);
                break;

            default:
                EventLog::log('event.unknown', "Unknown event type: {$payload['event']}");
        }
    }

    


    // returns true if this notification was already received
    protected function checkOrCreateNotificationReceipt($xchain_notification) {
        $uuid = $xchain_notification['notificationId'];

        try {
            $this->notification_receipt_repository->createByUUID($uuid);
        } catch (QueryException $e) {
            if ($e->errorInfo[0] == 23000) {
                EventLog::logError('notification.duplicate', ['notificationUuid' => $uuid]);
                return true;
            }
            throw $e;
        }

        return false;
    }


}
