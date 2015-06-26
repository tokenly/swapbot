<?php

namespace Swapbot\Http\Requests\Settings\Validators;

use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Factory;


class SettingValidator {

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
        return $validator;
    }



}
