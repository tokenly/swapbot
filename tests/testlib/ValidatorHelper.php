<?php

use Illuminate\Contracts\Validation\ValidationException;
use \PHPUnit_Framework_Assert as PHPUnit;

class ValidatorHelper  {

    function __construct() {
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function runTests($test_specs, $validator, $extra_validator_args=null) {
        // run all tests
        foreach($test_specs as $test_spec_offset => $test_spec) {
            $was_valid = null;
            $errors_string = null;
            try {

                // call $validator->validate($test_spec['vars'], ...)
                $args = [$test_spec['vars']];
                if ($extra_validator_args) { $args = array_merge($args, $extra_validator_args); }
                call_user_func_array([$validator, 'validate'], $args);

                $was_valid = true;
                $errors_string = false;
            } catch (ValidationException $e) {
                $was_valid = false;
                $errors_string = implode("\n", $e->errors()->all());
            }
            
            if ($test_spec['error']) {
                // check errors
                PHPUnit::assertFalse($was_valid, "Test $test_spec_offset was valid when it should not have been.  Expected error: {$test_spec['error']}");
                PHPUnit::assertContains($test_spec['error'], $errors_string, "unexpected error in test $test_spec_offset.");
            } else {
                // no errors
                PHPUnit::assertTrue($was_valid, "Received unexpected error: $errors_string in test $test_spec_offset.");
            }
        }
    }

}