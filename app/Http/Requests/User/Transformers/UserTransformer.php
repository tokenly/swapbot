<?php

namespace Swapbot\Http\Requests\User\Transformers;

use Illuminate\Support\Facades\Log;

class UserTransformer {

    public function santizeAttributes($attributes, $rules) {

        $out = [];
        foreach (array_keys($rules) as $field_name) {
            if (isset($attributes[$field_name])) {
                $out[$field_name] = $attributes[$field_name];
            }
        }

        return $out;
    }


}
