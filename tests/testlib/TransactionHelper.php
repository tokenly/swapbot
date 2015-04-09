<?php

use Swapbot\Repositories\TransactionRepository;

class TransactionHelper  {

    function __construct(TransactionRepository $transaction_repository) {
        $this->transaction_repository = $transaction_repository;
    }

    public function sampleTransactionVars() {
        return [
            'txid'                => 'transactionid0000000000001',
            'confirmations'       => '0',
            'processed'           => '0',
            'balances_applied'    => '0',
            'billed_event_id'     => null,
            'xchain_notification' => null,
        ];
    }

    public function newSampleTransaction($bot=null, $vars=[]) {
        $attributes = array_replace_recursive($this->sampleTransactionVars(), $vars);
        if ($bot == null) {
            $bot = app()->make('BotHelper')->newSampleBot();
        }
        $attributes['bot_id'] = $bot['id'];

        // create the model
        return $this->transaction_repository->create($attributes);
    }

}
