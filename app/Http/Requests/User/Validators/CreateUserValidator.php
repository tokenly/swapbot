<?php

namespace Swapbot\Http\Requests\User\Validators;

use Swapbot\Http\Requests\User\Validators\UserValidator;

class CreateUserValidator extends UserValidator {

    protected $rules = [
        'uuid'    => '',
        'name'    => 'required',
        'email'   => 'required',
        'user_id' => 'numeric',
    ];

}
