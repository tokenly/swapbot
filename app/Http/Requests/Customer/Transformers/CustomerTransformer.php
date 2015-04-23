<?php

namespace Swapbot\Http\Requests\Customer\Transformers;

use Illuminate\Support\Facades\Log;
use Swapbot\Models\Data\IncomeRuleConfig;
use Swapbot\Models\Data\SwapConfig;

class CustomerTransformer {

    public function santizeAttributes($attributes, $rules) {

        $out = [];
        foreach (array_keys($rules) as $field_name) {
            $camel_field_name = camel_case($field_name);
            if (isset($attributes[$camel_field_name])) {
                // try camel first
                $out[$field_name] = $attributes[$camel_field_name];

            } else if (isset($attributes[$field_name])) {
                // fall back to snake
                $out[$field_name] = $attributes[$field_name];

            }
        }

        return $out;
    }


}
