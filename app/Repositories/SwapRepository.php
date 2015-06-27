<?php

namespace Swapbot\Repositories;

use Illuminate\Support\Facades\DB;
use Swapbot\Models\Bot;
use Swapbot\Models\Swap;
use Tokenly\RecordLock\Facade\RecordLock;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
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
    // Modify
    

}
