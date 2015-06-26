<?php

namespace Swapbot\Http\Requests\Settings\Transformers;

use InvalidArgumentException;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class SettingTransformer {

    public function santizeAttributes($attributes, $rules) {

        $out = [];
        foreach (array_keys($rules) as $field_name) {
            if (isset($attributes[$field_name])) {
                $out[$field_name] = $attributes[$field_name];
            }
        }

        // santize value
        if (isset($attributes['value'])) {
            $out['value'] = $this->santizeValue($attributes['value']);
        }

        return $out;
    }

    public function santizeValue($raw_value) {
        if (is_array($raw_value)) {
            $decoded = $raw_value;
        } else {
            $decoded = json_decode($raw_value, true);
        }
        if ($decoded === null) { throw new InvalidArgumentException("Unable to decode the settings value"); }

        return $decoded;
    }



}
