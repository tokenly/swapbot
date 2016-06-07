<?php

namespace Swapbot\Handlers\Events;

use Illuminate\Support\Facades\Log;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Events\Event;
use Swapbot\Events\SettingWasChanged;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;
use Swapbot\Models\Setting;
use Tokenly\LaravelEventLog\Facade\EventLog;
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

        // send all swapbot events to the all channel
        $this->pusher_client->send('/all_swapbot_events', $event);
    }

    public function sendSwapEventToPusher(Event $event) {
        // all swap events go to the bot
        $this->sendBotEventToPusher($event);
    }

    public function sendSwapstreamEventToPusher(Event $event) {
        $bot   = $event->bot;
        $event = $event->event;

        $this->pusher_client->send('/swapbot_swapstream_'.$bot['uuid'], $event);
    }


    public function sendBotstreamEventToPusher(Event $event) {
        $bot   = $event->bot;
        $event = $event->event;

        $this->pusher_client->send('/swapbot_botstream_'.$bot['uuid'], $event);
    }


    public function sendBalanceUpdateToPusher(Event $event) {
        $bot   = $event->bot;
        $balances = $event->new_balances;

        $this->pusher_client->send('/swapbot_balances_'.$bot['uuid'], $balances);
    }

    public function sendAccountUpdatedToPusher(Event $event) {
        $bot   = $event->bot;
        $this->pusher_client->send('/swapbot_account_updates_'.$bot['uuid'], ['accountUpdated' => true]);
    }

    public function sendBotUpdateToPusher(Event $event) {
        $bot         = $event->bot;
        $update_type = $event->update_type;

        $pusher_vars = [
            'id'          => Uuid::uuid4()->toString(),
            'isBotUpdate' => true,
            'bot'         => $bot->serializeForAPI('public'),
        ];

        $this->pusher_client->send('/swapbot_botstream_'.$bot['uuid'], $pusher_vars);
    }

    public function handleSettingUpdate(SettingWasChanged $event) {
        $setting = $event->setting;
        if ($setting['name'] == 'globalAlert') {
            $event_type = $event->event_type;
            Log::debug("handleSettingUpdate \$event_type=".json_encode($event_type, 192));

            // delete
            if ($event_type == 'delete') {
                $this->handleGlobalAlertChange(false, '');
                return;
            }

            $alert_data = $setting['value'];
            $status = (is_array($alert_data) AND isset($alert_data['status'])) ? $alert_data['status'] : null;
            if ($status === null) {
                EventLog::logError('event.invalid', ['name' => 'globalAlert', 'alert_data' => $alert_data,]);
                return;
            }
            $content = (is_array($alert_data) AND isset($alert_data['content'])) ? $alert_data['content'] : '';
            $this->handleGlobalAlertChange($alert_data['status'], $alert_data['content']);
        }
    }

    public function handleGlobalAlertChange($active, $raw_content) {
        $content = $raw_content;
        if (!$active) { $content = ''; }
        $pusher_vars = [
            'alertType' => 'global',
            'content'   => $content,
            'status'    => $active,
        ];
        // Log::debug("\$pusher_vars=".json_encode($pusher_vars, 192));

        $this->pusher_client->send('/swapbot_alerts', $pusher_vars);
    }


    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('Swapbot\Events\SwapEventCreated',         'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendSwapEventToPusher');
        $events->listen('Swapbot\Events\BotEventCreated',          'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendBotEventToPusher');
        $events->listen('Swapbot\Events\SwapstreamEventCreated',   'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendSwapstreamEventToPusher');
        $events->listen('Swapbot\Events\BotstreamEventCreated',    'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendBotstreamEventToPusher');

        $events->listen('Swapbot\Events\BotBalancesUpdated',       'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendBalanceUpdateToPusher');
        $events->listen('Swapbot\Events\BotPaymentAccountUpdated', 'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendAccountUpdatedToPusher');

        $events->listen('Swapbot\Events\BotUpdated',               'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@sendBotUpdateToPusher');

        $events->listen('Swapbot\Events\SettingWasChanged',        'Swapbot\Handlers\Events\BotUpdatesForDisplayHandler@handleSettingUpdate');
    }


}
