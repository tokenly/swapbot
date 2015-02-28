<?php

namespace Swapbot\Repositories;

use Swapbot\Models\Bot;
use Swapbot\Models\Swap;
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

    public function findByBotIDTransactionIDAndName($bot_id, $tx_id, $swap_name) {
        return $this->prototype_model
            ->where('bot_id', $bot_id)
            ->where('transaction_id', $tx_id)
            ->where('name', $swap_name)
            ->first();
    }
        
    public function findByBotIDWithStates($bot_id, $states) {
        return $this->prototype_model
            ->where('bot_id', $bot_id)
            ->whereIn('state', $states)
            ->get();
        
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Modify
    

}
