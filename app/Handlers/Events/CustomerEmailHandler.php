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
use Swapbot\Events\SwapWasPermanentlyErrored;
use Swapbot\Models\Customer;
use Swapbot\Models\Data\RefundConfig;
use Swapbot\Models\Formatting\FormattingHelper;
use Swapbot\Models\Swap;
use Swapbot\Repositories\CustomerRepository;
use Tokenly\PusherClient\Client;


class CustomerEmailHandler {

    use DispatchesCommands;

    function __construct(CustomerRepository $customer_repository, FormattingHelper $formatting_helper) {
        $this->customer_repository = $customer_repository;
        $this->formatting_helper      = $formatting_helper;
    }


    public function customerAddedToSwap(CustomerAddedToSwap $event) {
        $customer = $event->customer;
        $swap     = $event->swap;

        // filter by event level
        if ($customer['level'] > Customer::NOTIFICATION_LEVEL_ALL) { return; }

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

            // filter by event level
            if ($customer['level'] > Customer::NOTIFICATION_LEVEL_RECEIVED) { return; }


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

            // filter by event level
            if ($customer['level'] > Customer::NOTIFICATION_LEVEL_FINAL_ONLY) { return; }

            // build variables
            $email_vars = $this->buildEmailVariables($swap, $customer);

            // send an email
            if ($swap['receipt']['type'] == 'swap') {
                $send_email = new SendEmail('emails.notifications.complete', $email_vars, "Swap Complete", $customer['email'], null);
            } else if ($swap['receipt']['type'] == 'refund') {
                $send_email = new SendEmail('emails.notifications.refunded', $email_vars, "Your Swap was Refunded", $customer['email'], null);
            }
            $this->dispatch($send_email);
        }
    }

    // when a swap has been received and confirmed
    public function swapWasPermanentlyErrored(SwapWasPermanentlyErrored $event) {
        $swap = $event->swap;

        // find all customers for this swap
        $customers = $this->customer_repository->findBySwap($swap);
        foreach($customers as $customer) {
            if (!$customer->isActive()) { continue; }

            // build variables
            $email_vars = $this->buildEmailVariables($swap, $customer);

            // send an email
            $send_email = new SendEmail('emails.notifications.error', $email_vars, "Your Swap Had a Problem", $customer['email'], null);
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
        $events->listen('Swapbot\Events\SwapWasPermanentlyErrored', 'Swapbot\Handlers\Events\CustomerEmailHandler@swapWasPermanentlyErrored');
    }

    protected function buildEmailVariables($swap, $customer) {
        $bot = $swap->bot;

        $out_quantity  = $swap['receipt']['quantityOut'];
        $out_asset     = $swap['receipt']['assetOut'];
        $refund_reason_code = isset($swap['receipt']['refundReason']) ? $swap['receipt']['refundReason'] : RefundConfig::REASON_UNKNOWN;
        $refund_reason = RefundConfig::refundReasonCodeToRefundReasonDescription($refund_reason_code);

        $host = Config::get('swapbot.site_host');
        $unsubscribe_link = "$host/public/unsubscribe/{$customer['uuid']}/{$customer['unsubscribe_token']}";
        $bot_url = $bot->getPublicBotURL();
        $bot_link = '<a href="'.$bot_url.'">'.$bot['name'].'</a>';

        $serialized_swap = $swap->serializeForAPI();
        // Log::debug('$serialized_swap='.json_encode($serialized_swap, 192));
        $email_vars = [
            'customer'        => $customer->serializeForAPI(),
            'swap'            => $serialized_swap,
            'strategy'        => $swap->getSwapConfigStrategy(),
            'hasChange'       => isset($serialized_swap['receipt']['changeOut']) AND $serialized_swap['receipt']['changeOut'] > 0,
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
            'refundReason'    => $refund_reason,
        ];
        return $email_vars;
     

    }

}
