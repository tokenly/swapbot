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



    protected function modifyAttributesBeforeCreate($attributes) {
        $attributes['serial'] = round(microtime(true) * 1000);
        return $attributes;
    }


}
