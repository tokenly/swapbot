<?php

use Illuminate\Contracts\Validation\ValidationException;
use \PHPUnit_Framework_Assert as PHPUnit;

class ValidatorHelper  {

    function __construct() {
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function runTests($test_specs, $validator) {
        // run all tests
        foreach($test_specs as $test_spec_offset => $test_spec) {
            $was_valid = null;
            $errors_string = null;
            try {
                $validator->validate($test_spec['vars']);
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