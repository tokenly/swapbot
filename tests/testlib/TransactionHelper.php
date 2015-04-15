<?php

use Swapbot\Repositories\TransactionRepository;
use Tokenly\TokenGenerator\TokenGenerator;

class TransactionHelper  {

    function __construct(TransactionRepository $transaction_repository, TokenGenerator $token_generator) {
        $this->transaction_repository = $transaction_repository;
        $this->token_generator = $token_generator;
    }

    public function sampleTransactionVars() {
        return [
            'txid'                => 'transactionid0000000000001',
            'confirmations'       => '0',
            'processed'           => '0',
            'balances_applied'    => '0',
            'billed_event_id'     => null,
            'xchain_notification' => null,
            'type'                => 'receive',
        ];
    }

    public function newSampleTransaction($bot=null, $vars=[]) {
        $attributes = array_replace_recursive($this->sampleTransactionVars(), ['txid' => $this->randomTXID()],
         $vars);
        if ($bot == null) {
            $bot = app()->make('BotHelper')->newSampleBot();
        }
        $attributes['bot_id'] = $bot['id'];

        // create the model
        return $this->transaction_repository->create($attributes);
    }

    public function randomTXID() {
        return 'transactionid00000000000'.$this->token_generator->generateToken(40);
    }
}
