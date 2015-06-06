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
                'vars' => array_replace_recursive($sample_vars, ['payment_plan' => '']),
                'error' => 'The payment plan field is required.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['payment_plan' => 'badtype']),
                'error' => 'The selected payment plan is invalid.',
            ],
            [
                'vars' => array_merge($sample_vars, ['swaps' => []]),
                'error' => 'at least one swap',
            ],
            [
                'vars' => array_merge($sample_vars, ['return_fee' => '']),
                'error' => 'The return fee field is required.',
            ],
            [
                'vars' => array_merge($sample_vars, ['return_fee' => 0]),
                'error' => 'The return fee must be at least 0.00001.',
            ],
            [
                'vars' => array_merge($sample_vars, ['return_fee' => 0.002]),
                'error' => 'The return fee may not be greater than 0.001.',
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
            [
                'vars' => array_replace_recursive($sample_vars, ['confirmations_required' => '']),
                'error' => 'The confirmations required field is required.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['confirmations_required' => 0]),
                'error' => 'The confirmations required must be at least 2.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['confirmations_required' => 1]),
                'error' => 'The confirmations required must be at least 2.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['confirmations_required' => 7]),
                'error' => 'The confirmations required may not be greater than 6.',
            ],

            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [0 => ['min' => '-1',]]]),
                'error' => 'The minimum value for swap #1 was not valid',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [0 => ['min' => '',]]]),
                'error' => 'Please specify a valid minimum value for swap #1',
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
            // [
            //     'vars' => array_replace_recursive($fixed_sample_vars, ['swaps' => [0 => ['min' => '-1',]]]),
            //     'error' => 'The minimum value for swap #1 was not valid',
            // ],
            // [
            //     'vars' => array_replace_recursive($fixed_sample_vars, ['swaps' => [0 => ['min' => '',]]]),
            //     'error' => 'Please specify a valid minimum value for swap #1',
            // ],
        ]);


        // fiat
        $fiat_sample_vars = array_replace_recursive($this->app->make('BotHelper')->sampleBotVars(), ['swaps' => [0 => [
            'strategy'  => 'fiat',
            'in'        => 'BTC',
            'out'       => 'LTCOIN',
            'cost'      => 5,
            'min_out'   => 0,
            'divisible' => false,
            'type'      => 'buy',
            'fiat'      => 'USD',
            'source'    => 'bitcoinAverage',
        ]]]);
        $test_specs = array_merge($test_specs, [
            [
                'vars' => $fiat_sample_vars,
                'error' => null,
            ],
            [
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['cost' => 0,]]]),
                'error' => 'The cost for swap #1 was not valid.',
            ],
            [
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['cost' => 'bad',]]]),
                'error' => 'The cost for swap #1 was not valid.',
            ],

            [
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['min_out' => -1,]]]),
                'error' => 'The minimum output value for swap #1 was not valid.',
            ],
            [
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['type' => 'sell',]]]),
                'error' => 'Only type of buy is supported',
            ],
            [
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['fiat' => 'bad',]]]),
                'error' => 'Only USD is supported',
            ],
            [
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['source' => 'bad',]]]),
                'error' => 'Only bitcoinAverage is supported',
            ],
            [
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['in' => 'BAD',]]]),
                'error' => 'Only BTC is supported',
            ],
        ]);


        $validator = $this->app->make('Swapbot\Http\Requests\Bot\Validators\CreateBotValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator);
    }

    public function testBotBlankIncomeRulesValidation() {
        $validator = app('Swapbot\Http\Requests\Bot\Validators\CreateBotValidator');
        $transformer = app('Swapbot\Http\Requests\Bot\Transformers\BotTransformer');
        $sample_vars = $this->app->make('BotHelper')->sampleBotVars();
        $attributes = array_replace_recursive($sample_vars, ['incomeRules' => [0 => ['asset' => '','minThreshold' => '','paymentAmount' => '','address' => '',]]]);
        $sanitized_attributes = $transformer->santizeAttributes($attributes, $validator->getRules());
        PHPUnit::assertEmpty($sanitized_attributes['income_rules']);
    }


    public function testBotIncomeRulesValidation()
    {
        // sample bot
        $sample_vars = $this->app->make('BotHelper')->sampleBotVars();

        $test_specs = [
            [
                // blank income rules are ok 
                'vars' => array_replace_recursive($sample_vars, ['income_rules' => []]),
                'error' => null,
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['income_rules' => [0 => ['asset' => '',]]]),
                'error' => 'Please specify an asset for Income Rule #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['income_rules' => [0 => ['asset' => 'AXX',]]]),
                'error' => 'The asset name for Income Rule #1 was not valid',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['income_rules' => [0 => ['minThreshold' => '',]]]),
                'error' => 'Please specify a minimum threshold for Income Rule #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['income_rules' => [0 => ['minThreshold' => -1,]]]),
                'error' => 'Please specify a minimum threshold for Income Rule #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['income_rules' => [0 => ['paymentAmount' => '',]]]),
                'error' => 'Please specify a payment amount for Income Rule #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['income_rules' => [0 => ['paymentAmount' => -1,]]]),
                'error' => 'Please specify a payment amount for Income Rule #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['income_rules' => [0 => ['address' => '',]]]),
                'error' => 'Please specify a payment address for Income Rule #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['income_rules' => [0 => ['address' => 'abadaddress2',]]]),
                'error' => 'The payment address abadaddress2 was not a valid bitcoin address.',
            ],

        ];

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
                'vars' => ['return_fee' => 0],
                'error' => 'The return fee must be at least 0.00001.',
            ],
            [
                'vars' => ['return_fee' => 0.003],
                'error' => 'The return fee may not be greater than 0.001.',
            ],
            [
                'vars' => ['swaps' => []],
                'error' => 'at least one swap',
            ],
            [
                'vars' => ['swaps' => [0 => ['in' => '', 'out'  => 'LTBCOIN', 'rate' => 0.00000150,'strategy' => 'rate']]],
                'error' => 'specify an asset to receive for swap #1',
            ],
            [
                'vars' => ['confirmations_required' => 0],
                'error' => 'The confirmations required must be at least 2.',
            ],
            [
                'vars' => ['confirmations_required' => -1],
                'error' => 'The confirmations required must be at least 2.',
            ],
            [
                'vars' => ['confirmations_required' => 7],
                'error' => 'The confirmations required may not be greater than 6.',
            ],

            [
                'vars' => ['swaps' => [0 => ['in' => 'BTC', 'out'  => 'LTBCOIN', 'rate' => 0.00000150,'strategy' => 'rate', 'min' => '-1',]]],
                'error' => 'The minimum value for swap #1 was not valid',
            ],
            [
                'vars' => ['swaps' => [0 => ['in' => 'BTC', 'out'  => 'LTBCOIN', 'rate' => 0.00000150,'strategy' => 'rate', 'min' => '',]]],
                'error' => 'Please specify a valid minimum value for swap #1',
            ],
            [
                'vars' => ['swaps' => [0 => ['in' => 'BTC', 'out'  => 'LTBCOIN', 'rate' => 0.00000150,'strategy' => 'rate', 'min' => '0.5',]]],
                'error' => null, // valid
            ],

        ];

        $validator = $this->app->make('Swapbot\Http\Requests\Bot\Validators\UpdateBotValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator);
    }

    ////////////////////////////////////////////////////////////////////////
    
    


}
