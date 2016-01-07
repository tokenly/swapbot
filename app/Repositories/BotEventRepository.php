<?php

namespace Swapbot\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\BotEvent;
use Swapbot\Models\User;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use \Exception;

/*
* BotEventRepository
*/
class BotEventRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\BotEvent';

    public function findByBotId($bot_id) {
        return call_user_func([$this->model_type, 'where'], 'bot_id', $bot_id)
            ->orderBy('serial', 'desc')
            ->get();
    }

    public function findLatestSwapStreamEventsByBotId($bot_id, IndexRequestFilter $filter=null, $min_level=null) {
        if ($min_level === null) { $min_level = BotEvent::LEVEL_INFO; }

        // get all of the most recent event ids
        $all_swaps_results = call_user_func([$this->model_type, 'where'], 'bot_id', $bot_id)
            ->where('swap_stream', true)
            ->where('swap_id', '>', 0)
            ->where('level', '>=', $min_level)
            ->select(DB::raw('MAX(id) AS id'))
            ->groupBy('swap_id')
            ->get();
        $allowed_ids = [];
        foreach($all_swaps_results as $all_swaps_result) {
            $allowed_ids[] = $all_swaps_result['id'];
        }

        // only allow the event ids found above
        $query = call_user_func([$this->model_type, 'where'], 'bot_id', $bot_id)
            ->where('swap_stream', true)
            ->whereIn('id', $allowed_ids);

        if ($filter === null) {
            $query->orderBy('serial', 'asc');
        } else {
            $filter->limit($query);
            $filter->sort($query);
        }

        return $query->get();
    }

    public function findAllSwapStreamEventsByBotId($bot_id, IndexRequestFilter $filter=null) {
        $query = call_user_func([$this->model_type, 'where'], 'bot_id', $bot_id)
            ->where('swap_stream', true);

        if ($filter === null) {
            $query->orderBy('serial', 'asc');
        } else {
            $filter->limit($query);
            $filter->sort($query);
        }

        return $query->get();
    }


    public function findBotStreamEventsByBotId($bot_id, IndexRequestFilter $filter=null) {
        $query = call_user_func([$this->model_type, 'where'], 'bot_id', $bot_id)
            ->where('bot_stream', true);

        // limit
        if ($filter === null) {
            $query->orderBy('serial', 'asc');
        } else {
            $filter->limit($query);
            $filter->sort($query);
        }

        return $query->get();
    }


    public function findBySwapId($swap_id) {
        return call_user_func([$this->model_type, 'where'], 'swap_id', $swap_id)
            ->orderBy('serial', 'desc')
            ->get();
    }


    // note that this is a slow operation - do not use in production
    public function slowFindByEventName($event_name) {
        $iterator = call_user_func([$this->model_type, 'where'], 'event', 'LIKE', '%"name"%"'.$event_name.'"%')
            ->orderBy('serial', 'asc')
            ->get();

        foreach($iterator as $event_model) {
            if ($event_model['event']['name'] == $event_name) {
                yield $event_model;
            }
        }
    }

    public function archive($event) {
        DB::transaction(function() use ($event) {
            $create_vars = $event->getOriginal();

            // set date
            $create_vars['archived_at'] = Carbon::now();

            // create the new one
            DB::table('bot_events_archive')->insert($create_vars);

            // delete the old one
            self::delete($event);
        });
    }

    // ------------------------------------------------------------------------
    
    protected function modifyAttributesBeforeCreate($attributes) {
        $attributes['serial'] = round(microtime(true) * 1000);
        return $attributes;
    }

}
