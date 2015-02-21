<?php

namespace Swapbot\Repositories;

use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\User;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use \Exception;

/*
* BotRepository
*/
class BotRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\Bot';



    public function findByUser(User $user) {
        return $this->findByUserID($user['id']);
    }

    public function findByUserID($user_id) {
        return call_user_func([$this->model_type, 'where'], 'user_id', $user_id)->get();
    }

    public function findByPublicMonitorID($monitor_id) {
        return call_user_func([$this->model_type, 'where'], 'public_receive_monitor_id', $monitor_id)->first();
    }

    public function findByPaymentMonitorID($monitor_id) {
        return call_user_func([$this->model_type, 'where'], 'payment_receive_monitor_id', $monitor_id)->first();
    }

    public function findBySendMonitorID($monitor_id) {
        return call_user_func([$this->model_type, 'where'], 'public_send_monitor_id', $monitor_id)->first();
    }



    protected function modifyAttributesBeforeCreate($attributes) {
        if (!isset($attributes['active'])) { $attributes['active'] = false; }

        // default to the unpaid state
        if (!isset($attributes['state'])) { $attributes['state'] = BotState::BRAND_NEW; }

        return $attributes;
    }

}
