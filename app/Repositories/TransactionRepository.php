<?php

namespace Swapbot\Repositories;

use Swapbot\Models\Transaction;
use Swapbot\Models\User;
use \Exception;

/*
* TransactionRepository
*/
class TransactionRepository
{

    protected $model_type = 'Swapbot\Models\Transaction';

    public function findByID($id) {
        return call_user_func([$this->model_type, 'find'], $id);
    }

    public function findByTransactionIDAndBotID($txid, $bot_id) {
        return call_user_func([$this->model_type, 'where'], 'txid', $txid)->where('bot_id', $bot_id)->first();
    }

    public function findByBotID($bot_id) {
        return call_user_func([$this->model_type, 'where'], 'bot_id', $bot_id)->get();
    }

    public function findByTransactionIDAndBotIDWithLock($txid, $bot_id, $type) {
        return 
            call_user_func([$this->model_type, 'where'], 'txid', $txid)
            ->where('bot_id', $bot_id)
            ->where('type', $type)
            ->lockForUpdate()
            ->first();
    }

    public function update(Transaction $model, $attributes) {
        return $model->update($attributes);
    }

    public function delete(Transaction $model) {
        return $model->delete();
    }

    public function create($attributes) {
        $attributes = $this->modifyAttributesBeforeCreate($attributes);

        return call_user_func([$this->model_type, 'create'], $attributes);
    }

    public function findAll() {
        return call_user_func([$this->model_type, 'all']);
    }


    public function findOrCreateTransaction($txid, $bot_id, $type, $other_vars=null) {
        $transaction_model = $this->findByTransactionIDAndBotIDWithLock($txid, $bot_id, $type);
        if ($transaction_model) { return $transaction_model; }

        // create a new transaction model
        $create_vars = ['bot_id' => $bot_id, 'txid' => $txid, 'type' => $type];
        if ($other_vars !== null) { $create_vars = array_merge($other_vars, $create_vars); }
        return $this->create($create_vars);
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Modify
    
    protected function modifyAttributesBeforeCreate($attributes) {
        return $attributes;
    }

}
