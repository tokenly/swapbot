<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class UserValidatorTest extends TestCase {

    public function testUserCreateValidator()
    {
        // most be a user
        $sample_vars = $this->app->make('UserHelper')->sampleVars();

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
                'vars' => array_replace_recursive($sample_vars, ['email' => '']),
                'error' => 'The email field is required.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['user_id' => 'xyz']),
                'error' => 'The user id must be a number.',
            ],
        ];

        $validator = $this->app->make('Swapbot\Http\Requests\User\Validators\CreateUserValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator);
    }


    public function testUserUpdateValidator() {
        $test_specs = [
            [
                'vars' => ['name' => 'Updated name'],
                'error' => null,
            ],
            [
                'vars' => ['name' => ''],
                'error' => 'The name field is required.',
            ],
            [
                'vars' => ['email' => ''],
                'error' => 'The email field is required.',
            ],
        ];

        $validator = $this->app->make('Swapbot\Http\Requests\User\Validators\UpdateUserValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator);
    }

    ////////////////////////////////////////////////////////////////////////
    
    


}
