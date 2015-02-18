<?php

namespace Swapbot\Http\Requests\Bot\Validators;

use Swapbot\Http\Requests\Bot\Validators\BotValidator;

class CreateBotValidator extends BotValidator {

    protected $rules = [
        'uuid'        => '',
        'name'        => 'required',
        'description' => 'required',
        'user_id'     => 'numeric',
        'return_fee'  => 'required|numeric|min:0.00001',
    ];

}
