<?php

use Swapbot\Models\Bot;
use Swapbot\Models\Swap;
use Swapbot\Repositories\ConsumerRepository;

class ConsumerHelper  {

    function __construct(ConsumerRepository $consumer_repository) {
        $this->consumer_repository = $consumer_repository;
    }

    public function sampleConsumerVars() {
        return [
            'email'  => 'consumer@tokenly.co',
            'active' => 1
        ];
    }

    // creates a sample swap
    //   directly in the repository (no validation)
    public function newSampleConsumer(Swap $swap=null, $consumer_vars=[]) {
        $attributes = array_replace_recursive($this->sampleConsumerVars(), $consumer_vars);

        if (!isset($attributes['swap_id'])) {
            if ($swap == null) { $swap = app('SwapHelper')->newSampleSwap(); }
            $attributes['swap_id'] = $swap['id'];
        }

        $consumer_model = $this->consumer_repository->create($attributes);
        return $consumer_model;
    }


}
