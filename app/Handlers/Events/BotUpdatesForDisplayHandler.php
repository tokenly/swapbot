<?php

namespace Swapbot\Handlers\Events;

use Illuminate\Support\Facades\Log;
use Swapbot\Events\Event;
use Tokenly\PusherClient\Client;


class BotUpdatesForDisplayHandler {

    function __construct(Client $pusher_client) {
        $this->pusher_client = $pusher_client;
    }


    public function sendBotEventToPusher(Event $event) {
        $bot   = $event->bot;
        $event = $event->event;

        // Log::debug('sendBotEventToPusher: '.'/swapbot_events_'.$bot['uuid']);
        $this->pusher_client->send('/swapbot_events_'.$bot['uuid'], $event);
    }

    public function sendBalanceUpdateToPusher(Event $event) {
        $bot   = $event->bot;
        $balances = $event->new_balances;

        $this->pusher_client->send('/swapbot_balances_'.$bot['uuid'], $balances);
    }

    public function sendAccountUpdatedToPusher(Event $event) {
        $bot   = $event->bot;

        // Log::debug("sending to ".'/swapbot_account_updates_'.$bot['uuid']);
        $this->pusher_client->send('/swapbot_account_updates_'.$bot['uuid'], ['accountUpdated' => true]);
    }


    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('Swapbot\Events\BotEventCreated', 'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendBotEventToPusher');
        $events->listen('Swapbot\Events\BotBalancesUpdated', 'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendBalanceUpdateToPusher');
        $events->listen('Swapbot\Events\BotPaymentAccountUpdated', 'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendAccountUpdatedToPusher');
    }


}
