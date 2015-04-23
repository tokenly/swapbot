<?php

namespace Swapbot\Handlers\Events;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\SendEmail;
use Swapbot\Events\CustomerAddedToSwap;
use Swapbot\Events\Event;
use Swapbot\Events\SwapWasCompleted;
use Swapbot\Events\SwapWasConfirmed;
use Swapbot\Models\Customer;
use Swapbot\Models\Swap;
use Swapbot\Repositories\CustomerRepository;
use Tokenly\PusherClient\Client;


class CustomerEmailHandler {

    use DispatchesCommands;

    function __construct(CustomerRepository $customer_repository) {
        $this->customer_repository = $customer_repository;
    }


    public function customerAddedToSwap(CustomerAddedToSwap $event) {
        $customer = $event->customer;
        $swap     = $event->swap;

        // send an email
        $send_email = new SendEmail('emails.notifications.welcome', [], "Swap Request Received", $customer['email'], null);
        $this->dispatch($send_email);

    }

    // when a swap has been received and confirmed
    public function swapWasConfirmed(SwapWasConfirmed $event) {
        $swap = $event->swap;

        // find all customers for this swap
        $customers = $this->customer_repository->findBySwap($swap);
        foreach($customers as $customer) {
            if (!$customer->isActive()) { continue; }

            // send an email
            $send_email = new SendEmail('emails.notifications.received', [], "SwapBot Payment Received", $customer['email'], null);
            $this->dispatch($send_email);
        }
    }

    // when a swap has been received and confirmed
    public function swapWasCompleted(SwapWasCompleted $event) {
        $swap = $event->swap;

        // find all customers for this swap
        $customers = $this->customer_repository->findBySwap($swap);
        foreach($customers as $customer) {
            if (!$customer->isActive()) { continue; }

            // send an email
            $send_email = new SendEmail('emails.notifications.complete', [], "Swap Complete", $customer['email'], null);
            $this->dispatch($send_email);
        }
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
        $events->listen('Swapbot\Events\SwapWasConfirmed', 'Swapbot\Handlers\Events\CustomerEmailHandler@swapWasConfirmed');
        $events->listen('Swapbot\Events\SwapWasCompleted', 'Swapbot\Handlers\Events\CustomerEmailHandler@swapWasCompleted');
    }


}
