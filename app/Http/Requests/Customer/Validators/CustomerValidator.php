<?php

namespace Swapbot\Http\Requests\Customer\Validators;

use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;


class CustomerValidator {

    protected $swaps_required = true;
    protected $income_rules_required = false;

    function __construct(Factory $validator_factory) {
        $this->validator_factory     = $validator_factory;

        $this->initValidatorRules();
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


    protected function initValidatorRules() {
        // abstract method
    }
}
