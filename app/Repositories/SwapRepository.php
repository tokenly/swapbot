<?php

namespace Swapbot\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Swapbot\Models\Bot;
use Swapbot\Models\Swap;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use Tokenly\RecordLock\Facade\RecordLock;
use \Exception;

/*
* SwapRepository
*/
class SwapRepository extends APIRepository
{

    const DEFAULT_LOCKED_SWAP_TIMEOUT = 120; // 2 minutes

    protected $model_type = 'Swapbot\Models\Swap';

    public function findByBot(Bot $bot) {
        return $this->findByBotId($bot['id']);
    }

    public function findByBotId($bot_id, IndexRequestFilter $filter=null) {
        $query = $this->prototype_model->where('bot_id', $bot_id);

        if ($filter !== null) {
            $filter->apply($query);
        } else {
            $query->orderBy('id');
        }

        return $query->get();
    }

    public function findByBotIDTransactionIDAndName($bot_id, $transaction_id, $swap_name) {
        return $this->prototype_model
            ->where('bot_id', $bot_id)
            ->where('transaction_id', $transaction_id)
            ->where('name', $swap_name)
            ->first();
    }

    public function findByTransactionID($transaction_id) {
        return $this->prototype_model
            ->where('transaction_id', $transaction_id)
            ->orderBy('id')
            ->get();
    }
        
    public function findByBotIDWithStates($bot_id, $states) {
        return $this->prototype_model
            ->where('bot_id', $bot_id)
            ->whereIn('state', $states)
            ->orderBy('id')
            ->get();
        
    }

    public function findByStates($states) {
        return $this->prototype_model
            ->whereIn('state', $states)
            ->orderBy('id')
            ->get();
        
    }

    public function findAllWithBots(IndexRequestFilter $filter=null) {
        $query = DB::table('swaps')
            ->join('bots', 'bots.id', '=', 'swaps.bot_id')
            ->join('users', 'users.id', '=', 'bots.user_id')
            ->select(['swaps.*', 'bots.name AS bot_name', 'bots.uuid AS bot_uuid', 'users.username AS bot_username']);

        if ($filter !== null) {
            $filter->filter($query);
            $filter->limit($query);
            $filter->sort($query);
        }

        $swap = new Swap();
        return $swap->hydrate($query->get());
    }

    public function buildFindAllWithBotsFilterDefinition() {
        return [
            'fields' => [
                'state'     => ['field' => 'swaps.state',],
                'updatedAt' => ['sortField' => 'swaps.updated_at', 'defaultSortDirection' => 'desc'],
            ],
            'defaults' => [
            ],
        ];
    }


    // locks the swap, then executes $func inside the lock
    //   does not modify the passed Swap
    public function executeWithLockedSwap(Swap $swap, Callable $func) {
        return DB::transaction(function() use ($swap, $func) {
            return RecordLock::acquireAndExecute('swap'.$swap['id'], function() use ($swap, $func) {
                $locked_swap = $this->prototype_model->where('id', $swap['id'])->first();
                $out = $func($locked_swap);

                // update $swap in memory from any changes made to $locked_swap
                $swap->setRawAttributes($locked_swap->getAttributes());

                return $out;
            }, self::DEFAULT_LOCKED_SWAP_TIMEOUT);
        });
    }

    // merge update vars with the existing swap vars
    public function mergeUpdateVars(Swap $swap, $update_vars) {
        if (isset($update_vars['receipt'])) {
            $update_vars['receipt'] = array_merge($swap['receipt'], $update_vars['receipt']);
            // unset nulls
            foreach(array_keys($update_vars['receipt']) as $receipt_k) {
                if (is_null($update_vars['receipt'][$receipt_k])) {
                    unset($update_vars['receipt'][$receipt_k]);
                }
            }
        }


        return $update_vars;
    }


    public function buildFilterDefinition() {
        return [
            'fields' => [
                'state' => [
                    'field'     => 'state',
                    'sortField' => 'state',
                    'allow_multiple' => true,
                ],
                'updatedAt' => [
                    'sortField' => 'updated_at',
                    'defaultSortDirection' => 'desc',
                ],
                'createdAt' => [
                    'sortField' => 'created_at',
                    'defaultSortDirection' => 'desc',
                ],
            ],

            'defaults' => ['sort' => ['updatedAt','createdAt']],
            'limit' => ['max' => 100],
        ];
    }

}
