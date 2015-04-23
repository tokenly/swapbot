<?php

namespace Swapbot\Http\Requests\Customer\Validators;

use Swapbot\Http\Requests\Customer\Validators\CustomerValidator;


class CreateCustomerValidator extends CustomerValidator {

    protected $rules = [
        'uuid'    => '',
        'swap_id' => 'required|integer',
        'email'   => 'required|email',
    ];



}
