<?php

namespace Swapbot\Handlers\Events;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\SendEmail;
use Swapbot\Events\ConsumerAddedToSwap;
use Swapbot\Events\Event;
use Swapbot\Models\Consumer;
use Swapbot\Models\Swap;
use Tokenly\PusherClient\Client;


class ConsumerEmailHandler {

    use DispatchesCommands;

    function __construct() {
    }


    public function consumerAddedToSwap(ConsumerAddedToSwap $event) {
        $consumer = $event->consumer;
        $swap     = $event->swap;

        // send an email
        $send_email = new SendEmail('emails.notifications.welcome', [], "Swap Request Received", $consumer['email'], null);
        $this->dispatch($send_email);

    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('Swapbot\Events\ConsumerAddedToSwap', 'Swapbot\Handlers\Events\ConsumerEmailHandler@consumerAddedToSwap');
    }


}
