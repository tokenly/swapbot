<?php

namespace Swapbot\Handlers\Events;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Config;
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

        // build variables
        $email_vars = $this->buildEmailVariables($swap, $customer);

        // send an email
        $send_email = new SendEmail('emails.notifications.welcome', $email_vars, "Swap Request Received", $customer['email'], null);
        $this->dispatch($send_email);

    }

    // when a swap has been received and confirmed
    public function swapWasConfirmed(SwapWasConfirmed $event) {
        $swap = $event->swap;

        // find all customers for this swap
        $customers = $this->customer_repository->findBySwap($swap);
        foreach($customers as $customer) {
            if (!$customer->isActive()) { continue; }

            // build variables
            $email_vars = $this->buildEmailVariables($swap, $customer);

            // send an email
            $send_email = new SendEmail('emails.notifications.received', $email_vars, "SwapBot Payment Received", $customer['email'], null);
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

            // build variables
            $email_vars = $this->buildEmailVariables($swap, $customer);

            // send an email
            $send_email = new SendEmail('emails.notifications.complete', $email_vars, "Swap Complete", $customer['email'], null);
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

    protected function buildEmailVariables($swap, $customer) {
        $bot = $swap->bot;

        $out_quantity = $swap['receipt']['quantityOut'];
        $out_asset    = $swap['receipt']['assetOut'];

        // change will be here

        $host = Config::get('swapbot.site_host');
        $unsubscribe_link = "$host/public/unsubscribe/{$customer['uuid']}/{$customer['unsubscribe_token']}";
        $bot_url = $bot->getPublicBotURL();
        $bot_link = '<a href="'.$bot_url.'">'.$bot['name'].'</a>';

        $email_vars = [
            'customer'        => $customer->serializeForAPI(),
            'swap'            => $swap->serializeForAPI(),
            'bot'             => $bot->serializeForAPI(),
            'inQty'           => $swap['in_qty'],
            'inAsset'         => $swap['in_asset'],
            'outQty'          => $out_quantity,
            'outAsset'        => $out_asset,
            'host'            => $host,
            'unsubscribeLink' => $unsubscribe_link,
            'robohashUrl'     => $bot->getRobohashURL(),
            'botUrl'          => $bot_url,
            'botLink'         => $bot_link,
        ];
        return $email_vars;
        
    }

}
