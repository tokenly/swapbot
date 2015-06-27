<?php

namespace Swapbot\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\User;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use Tokenly\RecordLock\Facade\RecordLock;
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

    public function findByUuidAndUserID($uuid, $user_id) {
        return $this->prototype_model
            ->where('uuid', $uuid)
            ->where('user_id', $user_id)
            ->first();
    }



    public function findByUserID($user_id) {
        return $this->prototype_model->where('user_id', $user_id)->get();
    }

    public function findByPublicMonitorID($monitor_id) {
        return $this->prototype_model->where('public_receive_monitor_id', $monitor_id)->first();
    }

    public function findByPaymentMonitorID($monitor_id) {
        return $this->prototype_model->where('payment_receive_monitor_id', $monitor_id)->first();
    }

    public function findBySendMonitorID($monitor_id) {
        return $this->prototype_model->where('public_send_monitor_id', $monitor_id)->first();
    }

    public function findByPaymentSendMonitorID($monitor_id) {
        return $this->prototype_model->where('payment_send_monitor_id', $monitor_id)->first();
    }



    // locks the bot, then executes $func inside the lock
    //   does not modify the passed Bot
    public function executeWithLockedBot(Bot $bot, Callable $func) {
        return DB::transaction(function() use ($bot, $func) {
            return RecordLock::acquireAndExecute('bot'.$bot['id'], function() use ($bot, $func) {
                $locked_bot = $this->prototype_model->where('id', $bot['id'])->first();
                $out = $func($locked_bot);

                // update $bot in memory from any changes made to $locked_bot
                $bot->setRawAttributes($locked_bot->getAttributes());

                return $out;
            });
        });
    }


    public function create($attributes) {
        $model = parent::create($attributes);

        // force an update to build the hash
        $model_clone = clone $model;
        $this->update($model_clone, []);

        // apply the new hash
        $model['hash'] = $model_clone['hash'];

        return $model;
    }

    protected function modifyAttributesBeforeCreate($attributes) {
        if (!isset($attributes['active'])) { $attributes['active'] = false; }

        // default to the unpaid state
        if (!isset($attributes['state'])) { $attributes['state'] = BotState::BRAND_NEW; }

        return $attributes;
    }

    protected function modifyAttributesBeforeUpdate($attributes, Model $model) {
        // fill the attributes to a clone in memory to build the correct hash
        $model_clone = clone $model;
        $model_clone->fill($attributes);

        // assign the new hash to the attributes
        $attributes['hash'] = $model_clone->buildHash($attributes);

        return $attributes;
    }

}
