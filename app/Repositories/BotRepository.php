<?php

namespace Swapbot\Repositories;

use Swapbot\Models\Bot;
use Swapbot\Models\User;
use Swapbot\Repositories\Base\APIRepository;
use Swapbot\Repositories\Contracts\APIResourceRepositoryContract;
use \Exception;

/*
* BotRepository
*/
class BotRepository extends APIRepository implements APIResourceRepositoryContract
{

    protected $model_type = 'Swapbot\Models\Bot';



    public function findByUser(User $user) {
        return $this->findByUserID($user['id']);
    }

    public function findByUserID($user_id) {
        return call_user_func([$this->model_type, 'where'], 'user_id', $user_id)->get();
    }



    protected function modifyAttributesBeforeCreate($attributes) {
        if (!isset($attributes['active'])) { $attributes['active'] = false; }
        return $attributes;
    }

}
