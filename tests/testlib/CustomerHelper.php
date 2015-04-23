<?php

use Swapbot\Models\Bot;
use Swapbot\Models\Swap;
use Swapbot\Repositories\CustomerRepository;

class CustomerHelper  {

    function __construct(CustomerRepository $customer_repository) {
        $this->customer_repository = $customer_repository;
    }

    public function sampleCustomerVars() {
        return [
            'email'  => 'customer@tokenly.co',
            'active' => 1
        ];
    }

    // creates a sample swap
    //   directly in the repository (no validation)
    public function newSampleCustomer(Swap $swap=null, $customer_vars=[]) {
        $attributes = array_replace_recursive($this->sampleCustomerVars(), $customer_vars);

        if (!isset($attributes['swap_id'])) {
            if ($swap == null) { $swap = app('SwapHelper')->newSampleSwap(); }
            $attributes['swap_id'] = $swap['id'];
        }

        $customer_model = $this->customer_repository->create($attributes);
        return $customer_model;
    }


}
