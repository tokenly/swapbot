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

    protected function handleSwapEvent_swap_new(Swap $swap, Bot $bot, $event, $bot_event) {
        $fields = $this->buildSwapFields($swap, $bot);
        $this->sendEvent($this->buildSwapTitleData('New swap for ', $swap, $bot, $fields), $fields);
    }


    protected function handleSwapEvent_swap_stateChange(Swap $swap, Bot $bot, $event, $bot_event) {
        $method = 'handleSwapStateChangeEvent_'.str_replace('.', '_', $event['state']);
        if (method_exists($this, $method)) {
            $this->{$method}($swap, $bot, $event, $bot_event);
        }
    }

    protected function handleSwapStateChangeEvent_complete(Swap $swap, Bot $bot, $event, $bot_event) {
        $fields = $this->buildSwapFields($swap, $bot);
        $this->sendEvent($this->buildSwapTitleData('Completed swap for ', $swap, $bot, $fields), $fields);
    }


    /// ------------------------------------------------------------------------------------------------------------

    protected function buildSwapTitleData($title_prefix, $swap, $bot, $fields=null) {
        $title = $title_prefix.$bot['name'];
        $title_data = [
            'title'      => $title,
            'title_link' => $swap->getPublicSwapURL(),
            'thumb_url'  => $bot->getLogoImage(),
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
        KeenEvents::sendSlackEvent($data_or_title, $text_or_fields);
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
    }


}
