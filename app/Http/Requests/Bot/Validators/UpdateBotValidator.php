<?php

namespace Swapbot\Http\Requests\Bot\Validators;

use Swapbot\Http\Requests\Bot\Validators\BotValidator;

class UpdateBotValidator extends BotValidator {

    protected $swaps_required = false;

    protected $rules = [
        'name'        => 'sometimes|required',
        'description' => 'sometimes|required',
    ];

}
