<?php

namespace Swapbot\Http\Controllers\API\Base;

use Exception;
use Illuminate\Contracts\Validation\ValidationException;
use Swapbot\Http\Controllers\Controller;

class APIController extends Controller {

    protected $protected = true;

    public function __construct() {
        $this->addMiddleware();
    }

    public function addMiddleware() {
        // catch all errors and return a JSON response
        $this->middleware('api.catchErrors');

        if ($this->protected) {
            // require hmacauth middleware for all API requests
            $this->middleware('api.protectedAuth');
        }
    }

    protected function validateAttributesForAPI($attributes, array $rules, array $messages = [], array $customAttributes = []) {
        $validator = $this->getValidationFactory()->make($attributes, $rules, $messages, $customAttributes);

        if ($validator->fails()) { throw new ValidationException($validator); }

        $validated_attributes = [];
        foreach(array_keys($rules) as $rule_key) {
            if (isset($attributes[$rule_key])) {
                $validated_attributes[$rule_key] = $attributes[$rule_key];
            }
        }

        return $validated_attributes;
    }
}
