<?php

namespace Swapbot\Http\Requests\Bot\Validators;

use Illuminate\Contracts\Validation\ValidationException;
use Swapbot\Http\Requests\Bot\Validators\BotValidator;
use Swapbot\Models\Bot;
use Swapbot\Models\User;

class UpdateBotValidator extends BotValidator {

    protected $swaps_required = false;

    protected $rules = [
        'name'                        => 'sometimes|required',
        'url_slug'                    => 'sometimes|required|min:8|max:60',
        'description'                 => 'sometimes|required',
        'return_fee'                  => 'sometimes|numeric|min:0.0001|max:0.005',
        'confirmations_required'      => 'sometimes|integer|min:2|max:6',
        'background_image_id'         => 'sometimes',
        'logo_image_id'               => 'sometimes',
        'background_overlay_settings' => 'sometimes',
        'whitelist_uuid'              => 'sometimes',
    ];


    public function validateWithBot($posted_data, User $user, Bot $bot) {
        $validator = $this->buildValidator($posted_data, $user);

        $this->appendUpdateValidationRules($validator, $posted_data, $bot);

        if (!$validator->passes()) {
            throw new ValidationException($validator);        
        }
    }

    protected function appendUpdateValidationRules($validator, $posted_data, $bot) {
        $validator->after(function ($validator) use ($posted_data, $bot) {
            if (isset($posted_data['url_slug']) AND strlen($posted_data['url_slug'])) {
                $other_bot = $this->bot_repository->findBySlug($posted_data['url_slug']);
                if ($other_bot AND $other_bot['id'] != $bot['id']) {
                    $validator->errors()->add('url_slug', $this->messages['url_slug.unique']);
                }
            }
        });

    }

}
