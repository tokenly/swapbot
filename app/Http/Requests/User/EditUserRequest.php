<?php

namespace Swapbot\Http\Requests\User;

use Swapbot\Http\Requests\Request;

class EditUserRequest extends Request {

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



}
