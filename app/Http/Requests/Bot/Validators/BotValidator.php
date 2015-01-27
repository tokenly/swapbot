<?php

namespace Swapbot\Http\Requests\Bot\Validators;

use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;

class BotValidator {

    protected $swaps_required = true;

    function __construct(Factory $validator_factory) {
        $this->validator_factory = $validator_factory;
    }

    protected $rules = [];


    public function getRules() {
        return $this->rules;
    }

    public function validate($posted_data) {
        $validator = $this->buildValidator($posted_data);
        if (!$validator->passes()) {
            throw new ValidationException($validator);        
        }

    }

    protected function buildValidator($posted_data) {
        $validator = $this->validator_factory->make($posted_data, $this->rules, $messages=[], $customAttributes=[]);
        $validator->after(function ($validator) use ($posted_data) {
            // validate address
            $this->validateSwaps(isset($posted_data['swaps']) ? $posted_data['swaps'] : null, $validator);
        });
        return $validator;
    }

    protected function validateSwaps($swaps, $validator) {
        if ($swaps === null) {
            if ($this->swaps_required) {
                $validator->errors()->add('swaps', "Please specify at least one swap.");
            }
            return;
        }

        if ($swaps) {
            foreach(array_values($swaps) as $offset => $swap) {
                $this->validateSwap($offset, $swap, $validator);
            }
        } else {
            // swaps were set but were empty
            $validator->errors()->add('swaps', "Please specify at least one swap.");
        }
    }

    protected function validateSwap($offset, $swap, $validator) {
        $in_value   = isset($swap['in']) ? $swap['in'] : null;
        $out_value  = isset($swap['out']) ? $swap['out'] : null;
        $rate_value = isset($swap['rate']) ? $swap['rate'] : null;

        $swap_number = $offset + 1;
        $exists = (strlen($in_value) OR strlen($out_value) OR strlen($rate_value));
        if ($exists) {
            $assets_are_valid = true;

            // in asset
            if (strlen($in_value)) {
                if (!$this->isValidAssetName($in_value)) {
                    $assets_are_valid = false;
                    $validator->errors()->add('in', "The receive asset name for swap #{$swap_number} was not valid.");
                }
            } else {
                $validator->errors()->add('in', "Please specify an asset to receive for swap #{$swap_number}");
            }

            // out asset
            if (strlen($out_value)) {
                if (!$this->isValidAssetName($out_value)) {
                    $assets_are_valid = false;
                    $validator->errors()->add('out', "The send asset name for swap #{$swap_number} was not valid.");
                }
            } else {
                $validator->errors()->add('out', "Please specify an asset to send for swap #{$swap_number}");
            }

            // rate
            if (strlen($rate_value)) {
                if (!$this->isValidRate($rate_value)) {
                    $validator->errors()->add('rate', "The rate for swap #{$swap_number} was not valid.");
                }
            } else {
                $validator->errors()->add('rate', "Please specify a valid rate for swap #{$swap_number}");
            }

            // make sure assets aren't the same
            if ($assets_are_valid AND $in_value == $out_value) {
                $validator->errors()->add('rate', "The assets to receive and send for swap #{$swap_number} should not be the same.");
            }
        } else {
            $validator->errors()->add('swaps', "The values specified for swap #{$swap_number} were not valid.");
        }


    }

    protected function isValidAssetName($name) {
        if ($name === 'BTC') { return true; }
        if (!preg_match('!^[A-Z]+$!', $name)) { return false; }
        if (strlen($name) < 4) { return false; }
        if (substr($name, 0, 1) == 'A') { return false; }
        if (strlen($name) > 12) { return false; }

        return true;
    }

    protected function isValidRate($rate) {
        $rate = floatval($rate);
        if ($rate <= 0) { return false; }



        return true;
    }

}
