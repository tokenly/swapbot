<?php

namespace Swapbot\Http\Requests\Bot\Validators;

use Swapbot\Http\Requests\Bot\Validators\BotValidator;

class UpdateBotValidator extends BotValidator {

    protected $swaps_required = false;

    protected $rules = [
        'name'                        => 'sometimes|required',
        'url_slug'                    => 'sometimes|required|min:8|max:60',
        'description'                 => 'sometimes|required',
        'return_fee'                  => 'sometimes|numeric|min:0.00001|max:0.001',
        'confirmations_required'      => 'sometimes|integer|min:2|max:6',
        'background_image_id'         => 'sometimes',
        'logo_image_id'               => 'sometimes',
        'background_overlay_settings' => 'sometimes',
    ];

}
