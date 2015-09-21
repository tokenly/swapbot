<?php

namespace Swapbot\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotCreated;
use Swapbot\Events\BotDeleted;
use Swapbot\Events\BotUpdated;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\User;
use Swapbot\Repositories\BotIndexRepository;
use Swapbot\Swap\DateProvider\Facade\DateProvider;
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

    public function findBySlugAndUserID($url_slug, $user_id) {
        return $this->prototype_model
            ->where('url_slug', $url_slug)
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
        parent::update($model_clone, []);

        // apply the new hash
        $model['hash'] = $model_clone['hash'];

        // fire an event
        Event::fire(new BotCreated(clone $model));

        return $model;
    }

    protected function modifyAttributesBeforeCreate($attributes) {
        if (!isset($attributes['active'])) { $attributes['active'] = false; }

        // default to the unpaid state
        if (!isset($attributes['state'])) { $attributes['state'] = BotState::BRAND_NEW; }

        // set last_changed_at to now
        if (!isset($attributes['last_changed_at'])) { $attributes['last_changed_at'] = DateProvider::now(); }

        return $attributes;
    }

    protected function modifyAttributesBeforeUpdate($attributes, Model $model) {
        // fill the attributes to a clone in memory to build the correct hash
        $model_clone = clone $model;
        $model_clone->fill($attributes);

        // assign the new hash to the attributes
        $old_hash = $model['hash'];
        $new_hash = $model_clone->buildHash($attributes);
        $attributes['hash'] = $new_hash;

        // set last_changed_at to now if the hash changed
        if ($new_hash != $old_hash) { $attributes['last_changed_at'] = DateProvider::now(); }

        return $attributes;
    }


    public function update(Model $model, $attributes) {
        $out = parent::update($model, $attributes);
        Event::fire(new BotUpdated(clone $model));
        return $out;
    }

    public function delete(Model $model) {
        $out = parent::delete($model);
        Event::fire(new BotDeleted(clone $model));
        return $out;
    }

    public function buildFindAllFilterDefinition() {
        return [
            'fields' => [
                'name'        => [
                    'useFilterFn' => function($query, $param_value, $params) {
                        $query
                            ->join('bot_index AS bidx1', 'bots.id','=','bidx1.bot_id')
                            ->where('bidx1.field', '=', BotIndexRepository::FIELD_NAME)
                            ->where('bidx1.contents', 'like', '%'.$param_value.'%')
                            ->groupBy('bots.id');
                    },
                    'useSortFn' => function($query, $parsed_sort_query, $params) {
                        $query
                            // ->select('bots.*','bidx1s.contents')
                            ->join('bot_index AS bidx1s', 'bots.id','=','bidx1s.bot_id')
                            ->where('bidx1s.field', '=', BotIndexRepository::FIELD_NAME)
                            ->orderBy('bidx1s.contents', isset($parsed_sort_query['direction']) ? $parsed_sort_query['direction'] : 'ASC');
                    },
                ],
                'description' => ['useFilterFn' => function($query, $param_value, $params) {
                    $query
                        ->join('bot_index AS bidx2', 'bots.id','=','bidx2.bot_id')
                        ->where('bidx2.field', '=', BotIndexRepository::FIELD_DESCRIPTION)
                        ->where('bidx2.contents', 'like', '%'.$param_value.'%')
                        ->groupBy('bots.id');
                }],
                'username'    => ['useFilterFn' => function($query, $param_value, $params) {
                    $query
                        ->join('bot_index AS bidx3', 'bots.id','=','bidx3.bot_id')
                        ->where('bidx3.field', '=', BotIndexRepository::FIELD_USERNAME)
                        ->where('bidx3.contents', 'like', '%'.$param_value.'%')
                        ->groupBy('bots.id');
                }],

                'inToken'     => ['useFilterFn' => function($query, $param_value, $params, $context) {
                    if (!isset($context['swidx1'])) {
                        $context['swidx1'] = true;
                        $query->join('swap_index AS swidx1', 'bots.id','=','swidx1.bot_id');
                    }
                    $query
                        ->where('swidx1.in', '=', strtoupper(trim($param_value)))
                        ->groupBy('bots.id');
                }],
                'outToken'    => ['useFilterFn' => function($query, $param_value, $params, $context) {
                    if (!isset($context['swidx1'])) {
                        $context['swidx1'] = true;
                        $query->join('swap_index AS swidx1', 'bots.id','=','swidx1.bot_id');
                    }
                    $query
                        ->where('swidx1.out', '=', strtoupper(trim($param_value)))
                        ->groupBy('bots.id');
                }],

                'cost'    => ['useSortFn' => function($query, $parsed_sort_query, $params, $context) {
                    if (!isset($context['swidx1'])) {
                        $context['swidx1'] = true;
                        $query->join('swap_index AS swidx1', 'bots.id','=','swidx1.bot_id');
                    }
                    $query
                        ->orderBy('swidx1.cost', isset($parsed_sort_query['direction']) ? $parsed_sort_query['direction'] : 'ASC');
                }],

                'created_at'  => ['sortField' => 'created_at', 'defaultSortDirection' => 'asc'],
                'state'       => ['field' => 'state', 'default' => 'active',],
            ],
            'defaults' => ['sort' => 'created_at'],
        ];
    }

}
