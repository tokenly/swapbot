<?php

namespace Swapbot\Swap\Stats;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\BotRepository;

class SwapStatsAggregator {


    function __construct(BotEventRepository $bot_event_repository, BotRepository $bot_repository) {
        $this->bot_event_repository = $bot_event_repository;
        $this->bot_repository       = $bot_repository;
    }


    public function buildStats_payments() {
        $aggregated_stats_by_bot_id = [];
        $event_name = 'payment.monthlyFeePurchased';
        $this->filterAllEventsByType($event_name, function($bot_event) use (&$aggregated_stats_by_bot_id) {
            $event_data = $bot_event['event'];
            list($bot_name, $bot_owner_name) = $this->resolveBotIDToBotNameAndOwner($bot_event['bot_id']);
            $aggregated_stats_by_bot_id[$bot_event['bot_id']][] = [
                'bot_id'   => $bot_event['bot_id'],
                'bot_name' => $bot_name,
                'username' => $bot_owner_name,
                'date'     => $bot_event['created_at']->toDateTimeString(),
                'months'   => isset($event_data['months']) ? $event_data['months'] : null,
                'asset'    => isset($event_data['asset']) ? $event_data['asset'] : null,
                'cost'     => isset($event_data['cost']) ? $event_data['cost'] : null,
            ];
        });

        $stats_lines = [];
        foreach ($aggregated_stats_by_bot_id as $bot_id => $events) {
            foreach($events as $event) {
                $stats_lines[] = $event;
            }
        };

        return $stats_lines;
    }

    public function buildStats_swaps() {
        $aggregated_stats_by_bot_id = [];
        $event_name = 'swap.stateChange';
        $this->filterAllEventsByType($event_name, function($bot_event) use (&$aggregated_stats_by_bot_id) {
            $event_data = $bot_event['event'];
            if ($event_data['state'] == 'complete') {
                list($bot_name, $bot_owner_name) = $this->resolveBotIDToBotNameAndOwner($bot_event['bot_id']);

                $swap = $bot_event['swap'];
                $swap_receipt = $swap['receipt'];

                $in_asset     = (isset($swap_receipt['assetIn'])     ? $swap_receipt['assetIn']     : '');
                $in_quantity  = (isset($swap_receipt['quantityIn'])  ? $swap_receipt['quantityIn']  : 0);
                $out_asset    = (isset($swap_receipt['assetOut'])    ? $swap_receipt['assetOut']    : '');
                $out_quantity = (isset($swap_receipt['quantityOut']) ? $swap_receipt['quantityOut'] : 0);

                $aggregated_stats_by_bot_id[$bot_event['bot_id']][] = [
                    'bot_id'       => $bot_event['bot_id'],
                    'bot_name'     => $bot_name,
                    'username'     => $bot_owner_name,
                    'date'         => $bot_event['created_at']->toDateTimeString(),

                    'in_asset'     => $in_asset,
                    'in_quantity'  => $in_quantity,

                    'out_asset'    => $out_asset,
                    'out_quantity' => $out_quantity,
                ];
            }
        });

        $stats_lines = [];
        foreach ($aggregated_stats_by_bot_id as $bot_id => $events) {
            foreach($events as $event) {
                $stats_lines[] = $event;
            }
        };

        return $stats_lines;
    }


    public function filterAllEventsByType($event_name, $callback) {
        Log::debug("start filterAllEventsByType");
        
        $offset = 0;
        $this->bot_event_repository->findAllByEventNameInChunks($event_name, function($bot_events) use ($event_name, $callback, &$offset) {
            foreach ($bot_events as $bot_event) {
                ++$offset;

                $count = $offset + 1;
                if ($count % 5000 == 1) {
                    Log::debug("Processed $count");
                }
                if ($bot_event['event']['name'] == $event_name) {
                    $callback($bot_event);
                }

                // debug break
                // if ($offset >= 20000) { return false; }
            }
        });
        return [];



        // $collection = $this->bot_event_repository->findAllById();
        // $total = count($collection);


        // $collection->chunk(100, function() {

        // });
        // foreach ($collection as $offset => $bot_event) {
        //     $count = $offset + 1;
        //     if ($offset % 500 == 1 OR $count >= $total) {
        //         Log::debug("Processed $count of $total");
        //     }
        //     if ($bot_event['event']['name'] == $event_name) {
        //         yield $bot_event;
        //     }
        // }
    }

    public function resolveBotIDToBotNameAndOwner($bot_id) {
        if (!isset($this->bot_names_cache)) { $this->bot_names_cache = []; }
        if (!isset($this->bot_names_cache[$bot_id])) {
            $bot = $this->bot_repository->findById($bot_id);
            if ($bot) {
                $bot_name = $bot['name'];
                $bot_owner = $bot->user['username'];
            } else {
                $bot_name = "Unknown Bot #$bot_id";
                $bot_owner = "Unknown";
            }
            $this->bot_names_cache[$bot_id] = [$bot_name, $bot_owner];
        }

        return $this->bot_names_cache[$bot_id];
    }
}
