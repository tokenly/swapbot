<?php

namespace Swapbot\Repositories;

use Swapbot\Models\BotEvent;
use Swapbot\Models\User;
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

    public function findSwapsEventStreamByBotId($bot_id) {
        return call_user_func([$this->model_type, 'where'], 'bot_id', $bot_id)
            ->where('swap_stream', true)
            ->orderBy('serial', 'asc')
            ->get();
    }

    public function findBotEventStreamByBotId($bot_id) {
        return call_user_func([$this->model_type, 'where'], 'bot_id', $bot_id)
            ->where('bot_stream', true)
            ->orderBy('serial', 'asc')
            ->get();
    }


    public function findBySwapId($swap_id) {
        return call_user_func([$this->model_type, 'where'], 'swap_id', $swap_id)
            ->orderBy('serial', 'desc')
            ->get();
    }



    protected function modifyAttributesBeforeCreate($attributes) {
        $attributes['serial'] = round(microtime(true) * 1000);
        return $attributes;
    }


}
