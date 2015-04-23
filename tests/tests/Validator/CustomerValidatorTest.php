<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class CustomerValidatorTest extends TestCase {

    public function testCustomerCreateValidator()
    {
        $sample_vars = $this->app->make('CustomerHelper')->sampleCustomerVars();
        $sample_vars['swap_id'] = 1;

        $test_specs = [
            [
                'vars' => $sample_vars,
                'error' => null,
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['email' => '']),
                'error' => 'The email field is required.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['email' => 'bademail']),
                'error' => 'The email must be a valid email address.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swap_id' => '']),
                'error' => 'The swap id field is required.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swap_id' => 'xyz']),
                'error' => 'The swap id must be an integer.',
            ],
        ];

        $validator = $this->app->make('Swapbot\Http\Requests\Customer\Validators\CreateCustomerValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator);
    }


    ////////////////////////////////////////////////////////////////////////
    
    


}
