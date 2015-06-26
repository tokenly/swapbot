<?php

namespace Swapbot\Http\Requests\Settings\Validators;

use Swapbot\Http\Requests\Settings\Validators\SettingValidator;

class CreateSettingValidator extends SettingValidator {

    protected $rules = [
        'name'  => 'required',
        'value' => 'required',
    ];

}
