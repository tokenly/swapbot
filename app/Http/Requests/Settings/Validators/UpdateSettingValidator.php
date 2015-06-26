<?php

namespace Swapbot\Http\Requests\Settings\Validators;

use Swapbot\Http\Requests\Settings\Validators\SettingValidator;

class UpdateSettingValidator extends SettingValidator {

    protected $swaps_required = false;

    protected $rules = [
        'name'  => 'sometimes|required',
        'value' => 'required',
    ];

}
