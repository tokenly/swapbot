<?php

namespace Swapbot\Repositories;

use Swapbot\Models\BotEvent;
use Swapbot\Models\User;
use Swapbot\Repositories\Base\APIRepository;
use Swapbot\Repositories\Contracts\APIResourceRepositoryContract;
use \Exception;

/*
* BotEventRepository
*/
class BotEventRepository extends APIRepository implements APIResourceRepositoryContract
{

    protected $model_type = 'Swapbot\Models\BotEvent';

    public function findByBotId($bot_id) {
        return call_user_func([$this->model_type, 'where'], 'bot_id', $bot_id)->get();
    }


}
