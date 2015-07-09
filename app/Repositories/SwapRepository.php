<?php

namespace Swapbot\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Swapbot\Models\Bot;
use Swapbot\Models\Swap;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use Tokenly\RecordLock\Facade\RecordLock;
use \Exception;

/*
* SwapRepository
*/
class SwapRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\Swap';

    public function findByBot(Bot $bot) {
        return $this->findByBotId($bot['id']);
    }

    public function findByBotId($bot_id) {
        return $this->prototype_model->where('bot_id', $bot_id)->orderBy('id')->get();
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

    public function findAllWithBots($filter_params=null, $order_by_field=null) {

        $query = DB::table('swaps')
            ->join('bots', 'bots.id', '=', 'swaps.bot_id')
            ->join('users', 'users.id', '=', 'bots.user_id')
            ->select(['swaps.*', 'bots.name AS bot_name', 'bots.uuid AS bot_uuid', 'users.username AS bot_username']);

        $filter_defs = [
            'state'     => ['field' => 'swaps.state',],
            'updatedAt' => ['sortField' => 'swaps.updated_at', 'sortDirection' => 'desc'],
        ];
        $this->filter($filter_params, $query, $filter_defs);
        $this->orderBy($order_by_field, $query, $filter_defs);

        $swap = new Swap();
        return $swap->hydrate($query->get());
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
            });
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

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function filter($filter_params, Builder $query, $filter_definitions) {
        if ($filter_params !== null) {
            foreach($filter_params as $param_key => $param_value) {
                if (isset($filter_definitions[$param_key]) AND isset($filter_definitions[$param_key]['field'])) {
                    $filter_def = $filter_definitions[$param_key];
                    $query->where($filter_def['field'], '=', $param_value);
                }
            }
        }
    }

    protected function orderBy($order_by_field, Builder $query, $filter_definitions) {
        if ($order_by_field !== null) {
            if (isset($filter_definitions[$order_by_field]) AND isset($filter_definitions[$order_by_field]['sortField'])) {
                $filter_def = $filter_definitions[$order_by_field];
                $direction = $filter_def['sortDirection'];
                $query->orderBy($filter_def['sortField'], $direction);
            }
        }
    }
    

}
