<?php

namespace Swapbot\Http\Requests\User\Validators;

use Swapbot\Http\Requests\User\Validators\UserValidator;

class UpdateUserValidator extends UserValidator {

    protected $swaps_required = false;

    protected $rules = [
        'name'  => 'sometimes|required',
        'email' => 'email|sometimes|required',
    ];

}
