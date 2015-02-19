<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Swap\Processor\ReceiveEventProcessor;
use Swapbot\Swap\Processor\SendEventProcessor;
use Tokenly\LaravelEventLog\Facade\EventLog;

class ReceiveWebhookHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(ReceiveEventProcessor $receive_event_processor, SendEventProcessor $send_event_processor)
    {
        $this->receive_event_processor = $receive_event_processor;
        $this->send_event_processor    = $send_event_processor;
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

        switch ($payload['event']) {
            case 'block':
                // new block event
                //  don't do anything here
                EventLog::log('block.received', $payload);
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

            default:
                EventLog::log('event.unknown', "Unknown event type: {$payload['event']}");
        }
    }

    


}
