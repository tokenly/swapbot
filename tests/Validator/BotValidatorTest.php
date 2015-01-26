<?php

use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotValidatorTest extends TestCase {

    public function testBotValidator()
    {
        // most be a user
        $sample_vars = $this->app->make('BotHelper')->sampleBotVars();

        $test_specs = [
            [
                'vars' => $sample_vars,
                'error' => null,
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['name' => '']),
                'error' => 'The name field is required.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['description' => '']),
                'error' => 'The description field is required.',
            ],
            [
                'vars' => array_merge($sample_vars, ['swaps' => []]),
                'error' => 'at least one swap',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [0 => ['in' => '',]]]),
                'error' => 'specify an asset to receive for swap #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [0 => ['out' => '',]]]),
                'error' => 'specify an asset to send for swap #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [0 => ['rate' => '',]]]),
                'error' => 'Please specify a valid rate for swap #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [0 => ['rate' => '-0.001',]]]),
                'error' => 'The rate for swap #1 was not valid.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [0 => ['out' => 'BTC',]]]),
                'error' => 'should not be the same',
            ],

            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [1 => ['in' => 'FOOC',]]]),
                'error' => 'specify an asset to send for swap #2',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [1 => ['in' => 'FOOC',]]]),
                'error' => 'Please specify a valid rate for swap #',
            ],
        ];

        $validator = $this->app->make('Swapbot\Http\Requests\Bot\Validators\CreateBotValidator');


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
                PHPUnit::assertFalse($was_valid, "Test $test_spec_offset was valid when it should not have been.");
                PHPUnit::assertContains($test_spec['error'], $errors_string, "unexpected error in test $test_spec_offset.");
            } else {
                // no errors
                PHPUnit::assertTrue($was_valid, "Received unexpected error: $errors_string in test $test_spec_offset.");
            }
        }
    }


}
