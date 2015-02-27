<?php

use Swapbot\Models\Bot;
use Swapbot\Repositories\SwapRepository;

class SwapHelper  {

    function __construct(SwapRepository $swap_repository) {
        $this->swap_repository = $swap_repository;
    }


    public function sampleSwapVars() {
        return [
            'state'      => 'brandnew',
            'name'       => 'SOUP:BTC',

            'definition' => [
                'in'       => 'BTC',
                'out'      => 'LTBCOIN',
                'strategy' => 'rate',
                'rate'     => 0.00000150,
            ],
            'receipt'    => [],
        ];
    }

    // creates a sample swap
    //   directly in the repository (no validation)
    public function newSampleSwap($bot=null, $transaction=null, $swap_vars=[]) {
        $attributes = array_replace_recursive($this->sampleSwapVars(), $swap_vars);
        if ($bot == null) { $bot = app('BotHelper')->newSampleBot(); }
        if ($transaction == null) { $transaction = app('TransactionHelper')->newSampleTransaction($bot); }

        if (!isset($attributes['bot_id'])) { $attributes['bot_id'] = $bot['id']; }
        if (!isset($attributes['transaction_id'])) { $attributes['transaction_id'] = $transaction['id']; }

        $swap_model = $this->swap_repository->create($attributes);
        return $swap_model;
    }




}
