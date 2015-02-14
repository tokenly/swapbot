<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class BotValidatorTest extends TestCase {

    public function testBotCreateValidator()
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
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [1 => ['in' => 'FOOC','strategy' => 'rate',]]]),
                'error' => 'specify an asset to send for swap #2',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [1 => ['in' => 'FOOC','strategy' => 'rate',]]]),
                'error' => 'Please specify a valid rate for swap #2',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['blacklist_addresses' => ['abadaddress1']]),
                'error' => 'not a valid bitcoin address.',
            ],

            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [0 => ['strategy' => null,]]]),
                'error' => 'Please specify a swap strategy for swap #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [1 => ['in' => 'FOOC','out' => 'BOOC','rate'=>1,]]]),
                'error' => 'Please specify a swap strategy for swap #2',
            ],
        ];

        // fixed
        $fixed_sample_vars = array_replace_recursive($this->app->make('BotHelper')->sampleBotVars(), ['swaps' => [0 => ['strategy' => 'fixed', 'in' => 'EARLY', 'in_qty' => 1, 'out' => 'LTCOIN', 'out_qty' => 10000]]]);
        $test_specs = array_merge($test_specs, [
            [
                'vars' => $fixed_sample_vars,
                'error' => null,
            ],
            [
                'vars' => array_replace_recursive($fixed_sample_vars, ['swaps' => [0 => ['in_qty' => 0,]]]),
                'error' => 'The receive quantity for swap #1 was not valid.',
            ],
            [
                'vars' => array_replace_recursive($fixed_sample_vars, ['swaps' => [0 => ['out_qty' => 0,]]]),
                'error' => 'The send quantity for swap #1 was not valid.',
            ],
            [
                'vars' => array_replace_recursive($fixed_sample_vars, ['swaps' => [0 => ['in' => 'BAD',]]]),
                'error' => 'The receive asset name for swap #1 was not valid.',
            ],
        ]);


        $validator = $this->app->make('Swapbot\Http\Requests\Bot\Validators\CreateBotValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator);
    }


    public function testBotUpdateValidator() {
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
                'vars' => ['description' => ''],
                'error' => 'The description field is required.',
            ],
            [
                'vars' => ['swaps' => []],
                'error' => 'at least one swap',
            ],
            [
                'vars' => ['swaps' => [0 => ['in' => '', 'out'  => 'LTBCOIN', 'rate' => 0.00000150,'strategy' => 'rate']]],
                'error' => 'specify an asset to receive for swap #1',
            ],
        ];

        $validator = $this->app->make('Swapbot\Http\Requests\Bot\Validators\UpdateBotValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator);
    }

    ////////////////////////////////////////////////////////////////////////
    
    


}
