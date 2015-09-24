<?php

namespace Swapbot\Handlers\Events;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Log;
use Swapbot\Events\Event;
use Swapbot\Models\Bot;
use Swapbot\Models\Formatting\FormattingHelper;
use Swapbot\Models\Swap;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\LaravelKeenEvents\Facade\KeenEvents;

class SlackEventsHandler {

    use DispatchesCommands;

    function __construct(FormattingHelper $formatting_helper) {
        $this->formatting_helper = $formatting_helper;
    }

    public function swapEventCreated(Event $laravel_event) {
        if (!KeenEvents::slackIsActive()) { return; }

        try {
            $bot   = $laravel_event->bot;
            $swap  = $laravel_event->swap;
            $bot_event = $laravel_event->event;
            $event = $bot_event['event'];

            $method = 'handleSwapEvent_'.str_replace('.', '_', $event['name']);
            if (method_exists($this, $method)) {
                $this->{$method}($swap, $bot, $event, $bot_event);
            }
        } catch (Exception $e) {
            EventLog::logError('slackEvent.error', $e, $laravel_event->event);
        }
    }


    public function botEventCreated(Event $laravel_event) {
        if (!KeenEvents::slackIsActive()) { return; }

        try {
            $bot   = $laravel_event->bot;
            $bot_event = $laravel_event->event;
            $event = $bot_event['event'];

            $method = 'handleBotEvent_'.str_replace('.', '_', $event['name']);
            if (method_exists($this, $method)) {
                $this->{$method}($bot, $event, $bot_event);
            }
        } catch (Exception $e) {
            EventLog::logError('slackEvent.error', $e, $laravel_event->event);
        }
    }

    // ------------------------------------------------------------
    protected function handleBotEvent_income_forwarded(Bot $bot, $event, $bot_event) {
        $fields = [];
        $fields[] = [
            'title' => 'Income Forwarded',
            'value' => $this->formatting_helper->formatCurrency($event['quantityOut'])." ".$event['assetOut'],
            'short' => true,
        ];
        $fields[] = [
            'title' => 'Destination',
            'value' => $event['destination'],
            'short' => true,
        ];
        $fields[] = [
            'title' => 'TXID',
            'value' => $event['txid'],
            'short' => false,
        ];
        $this->sendEvent($this->buildBotTitleData('Income forwarded for ', $bot, $fields), $fields);
    }

    // ------------------------------------------------------------


    protected function handleBotEvent_income_forward_failed(Bot $bot, $event, $bot_event) {
        $fields = [];
        $fields[] = [
            'title' => 'Error',
            'value' => $event['error'],
            'short' => false,
        ];
        $data = $this->buildBotTitleData('Income forwarding error for ', $bot, $fields);
        $data['color'] = 'warning';
        $this->sendEvent($data, $fields);
    }


    // ------------------------------------------------------------
    protected function handleSwapEvent_swap_new(Swap $swap, Bot $bot, $event, $bot_event) {
        $fields = $this->buildSwapFields($swap, $bot);
        $this->sendEvent($this->buildSwapTitleData('New swap for ', $swap, $bot, $fields), $fields);
    }


    // ------------------------------------------------------------
    protected function handleSwapEvent_swap_stateChange(Swap $swap, Bot $bot, $event, $bot_event) {
        $method = 'handleSwapStateChangeEvent_'.str_replace('.', '_', $event['state']);
        if (method_exists($this, $method)) {
            $this->{$method}($swap, $bot, $event, $bot_event);
        }
    }

    // ------------------------------------------------------------
    protected function handleSwapStateChangeEvent_complete(Swap $swap, Bot $bot, $event, $bot_event) {
        $fields = $this->buildSwapFields($swap, $bot);
        $this->sendEvent($this->buildSwapTitleData('Completed swap for ', $swap, $bot, $fields), $fields);
    }

    // ------------------------------------------------------------
    protected function handleSwapStateChangeEvent_error(Swap $swap, Bot $bot, $event, $bot_event) {
        $fields = $this->buildSwapFields($swap, $bot);
        $data = $this->buildSwapTitleData('Temporary error in swap for ', $swap, $bot, $fields);
        $data['color'] = 'warning';
        $this->sendEvent($data, $fields);
    }

    // ------------------------------------------------------------
    protected function handleSwapStateChangeEvent_permanenterror(Swap $swap, Bot $bot, $event, $bot_event) {
        $fields = $this->buildSwapFields($swap, $bot);
        $data = $this->buildSwapTitleData('Permanent error in swap for ', $swap, $bot, $fields);
        $data['color'] = 'danger';
        $this->sendEvent($data, $fields);
    }


    /// ------------------------------------------------------------------------------------------------------------


    protected function buildBotTitleData($title_prefix, $bot, $fields=null) {
        $title = $title_prefix.$bot['name'];
        $title_data = [
            'title'      => $title,
            'title_link' => $bot->getPublicBotURL(),
            'thumb_url'  => $bot['logo_image'],
        ];

        if ($fields) {
            $fallback = '';
            foreach($fields as $field) {
                $fallback .= $field['title'].": ".$field['value']." ";
            }
            $title_data['fallback'] = trim($title." | ".$fallback);
        }

        return $title_data;
    }
    protected function buildSwapTitleData($title_prefix, $swap, $bot, $fields=null) {
        $title = $title_prefix.$bot['name'];
        $title_data = [
            'title'      => $title,
            'title_link' => $swap->getPublicSwapURL(),
            'thumb_url'  => $bot['logo_image'],
        ];

        if ($fields) {
            $fallback = '';
            foreach($fields as $field) {
                $fallback .= $field['title'].": ".$field['value']." ";
            }
            $title_data['fallback'] = trim($title." | ".$fallback);
        }

        return $title_data;
    }

    protected function buildSwapFields($swap, $bot) {
        $fields = [];

        $receipt = $swap['receipt'];

        // receipt
        $fields[] = [
            'title' => 'Tokens In',
            'value' => $this->formatting_helper->formatCurrency($receipt['quantityIn'])." ".$receipt['assetIn'],
            'short' => true,
        ];
        $fields[] = [
            'title' => 'Tokens Out',
            'value' => $this->formatting_helper->formatCurrency($receipt['quantityOut'])." ".$receipt['assetOut'],
            'short' => true,
        ];
        $fields[] = [
            'title' => 'Address',
            'value' => $swap['address'],
            'short' => true,
        ];
        $fields[] = [
            'title' => 'State',
            'value' => $swap['state'],
            'short' => true,
        ];

        return $fields;
    }


    protected function sendEvent($data_or_title, $text_or_fields) {
        if ($this->globalWriteToApplicationLog()) {
            EventLog::log('slack.notification', ['data' => $data_or_title, 'fields' => $text_or_fields]);
        }

        KeenEvents::sendSlackEvent($data_or_title, $text_or_fields);
    }

    protected function globalWriteToApplicationLog() {
        if (!isset($this->global_write_slack_notifications_to_application_log)) {
            $this->global_write_slack_notifications_to_application_log = !!env('WRITE_SLACK_NOTIFICATIONS_TO_APPLICATION_LOG', false);
        }
        return $this->global_write_slack_notifications_to_application_log;
    }

    /// ------------------------------------------------------------------------------------------------------------

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events) {
        $events->listen('Swapbot\Events\SwapEventCreated', 'Swapbot\Handlers\Events\SlackEventsHandler@swapEventCreated');
        $events->listen('Swapbot\Events\BotEventCreated', 'Swapbot\Handlers\Events\SlackEventsHandler@botEventCreated');
    }



}
