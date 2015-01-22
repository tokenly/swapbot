<?php

namespace Swapbot\Http\Requests\Bot;

use Swapbot\Http\Requests\Request;

class EditBotRequest extends Request {

    public function rules() {
        return [
            'name'        => 'required',
            'description' => 'required',
        ];
    }

    public function authorize() {
        return true;
    }

    public function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();

        // validate assets
        $validator->after(function () use ($validator) {
            // validate address
            $this->validateAssets($this->getFilteredData(), $validator);
        });

        return $validator;
    }

    public function getFilteredData() {
        $out = [];

        $posted_data = $this->all();

        // general fields
        foreach (array_keys($this->rules()) as $field_name) {
            $out[$field_name] = $posted_data[$field_name];
        }

        // assets
        for ($i=1; $i <= 5; $i++) { 
            $in_field_name = 'asset_in_'.$i;
            $in_value = isset($posted_data[$in_field_name]) ? $posted_data[$in_field_name] : null;
            $out_field_name = 'asset_out_'.$i;
            $out_value = isset($posted_data[$out_field_name]) ? $posted_data[$out_field_name] : null;
            $rate_field_name = 'vend_rate_'.$i;
            $rate_value = isset($posted_data[$rate_field_name]) ? $posted_data[$rate_field_name] : null;

            $exists = (strlen($in_value) OR strlen($out_value) OR strlen($rate_value));
            if ($exists) {
                $out[$in_field_name] = $in_value;
                $out[$out_field_name] = $out_value;
                $out[$rate_field_name] = $rate_value;
            }
        }

        return $out;
    }


    protected function validateAssets($posted_data, $validator) {
        $any_exists = false;
        for ($i=1; $i <= 5; $i++) { 
            $in_field_name = 'asset_in_'.$i;
            $in_value = isset($posted_data[$in_field_name]) ? $posted_data[$in_field_name] : null;
            $out_field_name = 'asset_out_'.$i;
            $out_value = isset($posted_data[$out_field_name]) ? $posted_data[$out_field_name] : null;
            $rate_field_name = 'vend_rate_'.$i;
            $rate_value = isset($posted_data[$rate_field_name]) ? $posted_data[$rate_field_name] : null;

            $exists = (strlen($in_value) OR strlen($out_value) OR strlen($rate_value));
            if ($exists) {
                $any_exists = true;
                $assets_are_valid = true;

                // in asset
                if (strlen($in_value)) {
                    if (!$this->isValidAssetName($in_value)) {
                        $assets_are_valid = false;
                        $validator->errors()->add($in_field_name, "The receive asset name for swap #{$i} was not valid.");
                    }
                } else {
                    $validator->errors()->add($in_field_name, "Please specify an asset to receive for swap #{$i}");
                }

                // out asset
                if (strlen($out_value)) {
                    if (!$this->isValidAssetName($out_value)) {
                        $assets_are_valid = false;
                        $validator->errors()->add($out_field_name, "The send asset name for swap #{$i} was not valid.");
                    }
                } else {
                    $validator->errors()->add($out_field_name, "Please specify an asset to send for swap #{$i}");
                }

                // rate
                if (strlen($rate_value)) {
                    if (!$this->isValidRate($rate_value)) {
                        $validator->errors()->add($rate_field_name, "The rate for swap #{$i} was not valid.");
                    }
                } else {
                    $validator->errors()->add($rate_field_name, "Please specify a valid rate for swap #{$i}");
                }

                // make sure assets aren't the same
                if ($assets_are_valid AND $in_value == $out_value) {
                    $validator->errors()->add($rate_field_name, "The assets to receive and send for swap #{$i} should not be the same.");
                }
            }
        }

        if (!$any_exists) {
            $validator->errors()->add('asset_in_1', "Please specify at least one asset to receive.");
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
