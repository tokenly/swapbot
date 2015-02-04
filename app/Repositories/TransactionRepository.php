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

    public function findByTransactionIDAndBotIDWithLock($txid, $bot_id) {
        return call_user_func([$this->model_type, 'where'], 'txid', $txid)->where('bot_id', $bot_id)->lockForUpdate()->first();
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

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Modify
    
    protected function modifyAttributesBeforeCreate($attributes) {
        return $attributes;
    }

}
