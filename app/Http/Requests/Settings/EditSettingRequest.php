<?php

namespace Swapbot\Http\Requests\Settings;

use Swapbot\Http\Requests\Request;

class EditSettingRequest extends Request {

    public function rules() {
        return [
            'name'  => 'required',
            'value' => 'required',
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
            $posted_data = $this->all();
            $this->validateValue($posted_data, $validator);
        });

        return $validator;
    }


    protected function validateValue($posted_data, $validator) {
        // code
    }

}
