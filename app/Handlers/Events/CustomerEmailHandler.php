<?php

namespace Swapbot\Handlers\Events;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\SendEmail;
use Swapbot\Events\CustomerAddedToSwap;
use Swapbot\Events\Event;
use Swapbot\Models\Customer;
use Swapbot\Models\Swap;
use Tokenly\PusherClient\Client;


class CustomerEmailHandler {

    use DispatchesCommands;

    function __construct() {
    }


    public function customerAddedToSwap(CustomerAddedToSwap $event) {
        $customer = $event->customer;
        $swap     = $event->swap;

        // send an email
        $send_email = new SendEmail('emails.notifications.welcome', [], "Swap Request Received", $customer['email'], null);
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
        $events->listen('Swapbot\Events\CustomerAddedToSwap', 'Swapbot\Handlers\Events\CustomerEmailHandler@customerAddedToSwap');
    }


}
