<?php

namespace Swapbot\Handlers\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Log;
use Swapbot\Events\Event;
use Tokenly\LaravelKeenEvents\Facade\KeenEvents;


class KeenEventsHandler {

    use DispatchesCommands;

    function __construct() {

        $this->collection_prefix = env('KEEN_COLLECTION_PREFIX', '');
    }

    public function botEventCreated(Event $event) {
        $bot = $event->bot;

    }

    public function swapEventCreated(Event $event) {
        if (!KeenEvents::keenIsActive()) { return; }

        $bot   = $event->bot;
        $swap  = $event->swap;
        $bot_event = $event->event;
        $event = $bot_event['event'];

        // handle various types of swap events
        // {"name":"swap.stateChange","state":"complete","isComplete":true,"isError":false}
        if ($event['name'] == 'swap.stateChange' AND $event['state'] == 'complete') {
            // handle a complete swap
            $in_btc_value = $this->swapBTCValue('in', $swap['receipt']);
            $out_btc_value = $this->swapBTCValue('out', $swap['receipt']);
            $collection = 'swap';
            $keen_event = [
                'name'          => 'swapComplete',
                'bot'           => $bot['uuid'],
                'swap'          => $swap['uuid'],
                'type'          => isset($swap['receipt']['type']) ? $swap['receipt']['type'] : 'unknown',

                'inBTCValue'    => $in_btc_value,
                'outBTCValue'   => $out_btc_value,
                'totalBTCValue' => $in_btc_value + $out_btc_value,

                'receipt'       => $swap['receipt'],

                'keen' => [
                    'timestamp' => $bot_event['createdAt'], // ISO 8601
                ],
            ];

            $this->sendEvent($collection, $keen_event);
        }

    }




    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events) {
        $events->listen('Swapbot\Events\BotEventCreated', 'Swapbot\Handlers\Events\KeenEventsHandler@botEventCreated');
        $events->listen('Swapbot\Events\SwapEventCreated', 'Swapbot\Handlers\Events\KeenEventsHandler@swapEventCreated');
    }

    protected function swapBTCValue($direction, $swap_receipt) {
        if ($direction == 'in') {
            if (isset($swap_receipt['assetIn']) AND $swap_receipt['assetIn'] == 'BTC') {
                return (isset($swap_receipt['quantityIn']) ? $swap_receipt['quantityIn'] : 0);
            }
        }
        if ($direction == 'out') {
            if (isset($swap_receipt['assetOut']) AND $swap_receipt['assetOut'] == 'BTC') {
                return (isset($swap_receipt['quantityOut']) ? $swap_receipt['quantityOut'] : 0);
            }
        }
        return 0;
    }


    protected function sendEvent($collection, $event) {
        // Log::debug("sendEvent to $collection:\n".json_encode($event, 192));
        KeenEvents::sendKeenEvent($this->collection_prefix.$collection, $event);
    }


/*
    'quantityIn'    => $swap_process['in_quantity'],
    'assetIn'       => $swap_process['in_asset'],
    'txidIn'        => $swap_process['transaction']['txid'],

    'quantityOut'   => $swap_process['quantity'],
    'assetOut'      => $swap_process['asset'],

    'confirmations' => $swap_process['confirmations'],
    'destination'   => $swap_process['destination'],

    'timestamp'     => time(),
 */

}
