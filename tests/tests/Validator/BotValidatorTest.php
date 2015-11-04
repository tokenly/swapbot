<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class BotValidatorTest extends TestCase {

    protected $use_database = true;

    public function testBotCreateValidator()
    {
        // must be a user
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
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [0 => ['direction' => 'up','strategy' => 'rate',]]]),
                'error' => 'Please specify a valid direction for swap #1',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['blacklist_addresses' => ['abadaddress1']]),
                'error' => 'not a valid bitcoin address.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['whitelist_addresses' => ['abadaddress1']]),
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
            [
                'vars' => array_replace_recursive($sample_vars, ['background_overlay_settings' => ['start' => 'rgba(0,0,0,1)', 'end' => 'rgba(0,0,0,2)']]),
                'error' => null,
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['background_overlay_settings' => ['start' => '#f00', 'end' => '#0f0']]),
                'error' => null,
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['background_overlay_settings' => ['start' => '%bad%', 'end' => 'rgba(0,0,0,2)']]),
                'error' => 'This gradient definition contained illegal characters.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['background_overlay_settings' => ['start' => 'bold', 'end' => 'rgba(0,0,0,2)']]),
                'error' => 'This gradient definition was not a valid color.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['background_overlay_settings' => ['start' => 'bold', 'end' => 'none']]),
                'error' => 'This gradient definition was not a valid color.',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['background_overlay_settings' => ['start' => 'bold', 'end' => '']]),
                'error' => 'This gradient was empty.',
            ],

            // refund config
            [
                'vars' => array_replace_recursive($sample_vars, ['refund_config' => ['refundAfterBlocks' => '2']]),
                'error' => '3 or more',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['refund_config' => ['refundAfterBlocks' => '73']]),
                'error' => 'no more than 72 confirmations',
            ],

            // slug
            [
                'vars' => array_replace_recursive($sample_vars, ['url_slug' => '2short']),
                'error' => 'url slug must be at least 8 characters',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['url_slug' => '2longxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx']),
                'error' => 'not be greater than 80 characters',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['url_slug' => 'my-slug-###']),
                'error' => 'not a valid URL slug',
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
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['cost' => 0.001,]]]),
                'error' => null,
            ],
            [
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['cost' => 0,]]]),
                'error' => 'The cost for swap #1 was not valid.',
            ],
            [
                'vars' => array_replace_recursive($fiat_sample_vars, ['swaps' => [0 => ['cost' => 0.000000005,]]]),
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

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator, [app('UserHelper')->getSampleUser()]);
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

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator, [app('UserHelper')->getSampleUser()]);
    }


    public function testBotSwapRulesValidation()
    {
        // sample bot
        $sample_vars = $this->app->make('BotHelper')->sampleBotVars();

        $sample_swap_rule = [
            'name' => 'My Rule One',
            'ruleType' => 'bulkDiscount',
            'uuid' => 'id0001',
            'discounts' => [[
                'moq' => '10',
                'pct' => '0.1',
            ],],
        ];

        $test_specs = [
            [
                // blank swap rules are ok 
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => []]),
                'error' => null,
            ],
            [
                // name required
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace_recursive($sample_swap_rule, [
                    'name' => '',
                ])]]),
                'error' => 'Please specify a name for Swap Rule #1',
            ],
            [
                // ruleType required
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace_recursive($sample_swap_rule, [
                    'ruleType' => '',
                ])]]),
                'error' => 'Please specify a type for Swap Rule #1',
            ],
            [
                // uuid required
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace_recursive($sample_swap_rule, [
                    'uuid' => '',
                ])]]),
                'error' => 'Please specify a UUID for Swap Rule #1',
            ],
            [
                // discounts required
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace($sample_swap_rule, [
                    'discounts' => []
                ])]]),
                'error' => 'Please specify discounts for Swap Rule #1',
            ],
            [
                // empty moq
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace_recursive($sample_swap_rule, [
                    'discounts' => [0 => [
                        'moq' => '',
                    ]],
                ])]]),
                'error' => 'Please specify a minimum order for Discount 1 of Swap Rule #1',
            ],
            [
                // bad moq
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace_recursive($sample_swap_rule, [
                    'discounts' => [0 => [
                        'moq' => -1,
                    ]],
                ])]]),
                'error' => 'The minimum order for Discount 1 of Swap Rule #1 was invalid',
            ],
            [
                // empty pct
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace_recursive($sample_swap_rule, [
                    'discounts' => [0 => [
                        'pct' => '',
                    ]],
                ])]]),
                'error' => 'Please specify a percentage for Discount 1 of Swap Rule #1',
            ],
            [
                // bad pct
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace_recursive($sample_swap_rule, [
                    'discounts' => [0 => [
                        'pct' => 0,
                    ]],
                ])]]),
                'error' => 'The percentage for Discount 1 of Swap Rule #1 was invalid',
            ],
            [
                // bad pct
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace_recursive($sample_swap_rule, [
                    'discounts' => [0 => [
                        'pct' => -1,
                    ]],
                ])]]),
                'error' => 'The percentage for Discount 1 of Swap Rule #1 was invalid',
            ],
            [
                // bad pct
                'vars' => array_replace_recursive($sample_vars, ['swap_rules' => [array_replace_recursive($sample_swap_rule, [
                    'discounts' => [0 => [
                        'pct' => 1.01,
                    ]],
                ])]]),
                'error' => 'The percentage for Discount 1 of Swap Rule #1 was invalid',
            ],

        ];

        $validator = $this->app->make('Swapbot\Http\Requests\Bot\Validators\CreateBotValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator, [app('UserHelper')->getSampleUser()]);
    }


    public function testApplliedBotSwapRulesValidation()
    {
        $sample_swap_rule = [
            'name' => 'My Rule One',
            'ruleType' => 'bulkDiscount',
            'uuid' => 'id0001',
            'discounts' => [[
                'moq' => '10',
                'pct' => '0.1',
            ],],
        ];

        // sample bot
        $sample_vars = $this->app->make('BotHelper')->sampleBotVars();
        $sample_vars = array_replace_recursive($sample_vars, ['swap_rules' => [$sample_swap_rule]]);

        $fixed_sample_vars = array_replace_recursive($sample_vars, ['swaps' => [0 => ['strategy' => 'fixed', 'in' => 'EARLY', 'in_qty' => 1, 'out' => 'LTCOIN', 'out_qty' => 10000]]]);

        $test_specs = [
            [
                // good uuid
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [
                    0 => ['swap_rule_ids' => ['id0001']],
                ]]),
                'error' => null,
            ],
            [
                // uuid required
                'vars' => array_replace_recursive($sample_vars, ['swaps' => [
                    0 => ['swap_rule_ids' => ['baduuid']],
                ]]),
                'error' => 'Please specify a valid id for this swap.',
            ],

        ];

        $validator = $this->app->make('Swapbot\Http\Requests\Bot\Validators\CreateBotValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator, [app('UserHelper')->getSampleUser()]);
    }
/*

    "swapRules": [
        {
            "discounts": [
                {
                    "moq": "10",
                    "pct": 0.1
                },
                {
                    "moq": "20",
                    "pct": 0.12
                },
                {
                    "moq": "30",
                    "pct": 0.13
                }
            ],
            "name": "Devon Rule One",
            "ruleType": "bulkDiscount",
            "uuid": "d3a11a3a-5b25-4f1d-0589-eca2fb212f47"
        }
    ],


 */
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

            [
                'vars' => ['url_slug' => 'my-slug-###'],
                'error' => 'not a valid URL slug',
            ],

        ];

        $validator = $this->app->make('Swapbot\Http\Requests\Bot\Validators\UpdateBotValidator');

        $this->app->make('ValidatorHelper')->runTests($test_specs, $validator, [app('UserHelper')->getSampleUser()]);
    }

    public function testBotWhitelistValidation()
    {
        $user        = app('UserHelper')->newRandomUser();
        $user_2      = app('UserHelper')->newRandomUser();
        $whitelist   = app('WhitelistHelper')->newSampleWhitelist($user);
        $whitelist_2 = app('WhitelistHelper')->newSampleWhitelist($user_2);

        // sample bot
        $sample_vars = app('BotHelper')->sampleBotVars();

        $test_specs = [
            [
                'vars' => array_replace_recursive($sample_vars, ['whitelist_uuid' => $whitelist['uuid']]),
                'error' => null, // valid
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['whitelist_uuid' => 'bad']),
                'error' => 'This whitelist was not found',
            ],
            [
                'vars' => array_replace_recursive($sample_vars, ['whitelist_uuid' => $whitelist_2['uuid']]),
                'error' => 'You do not have permission to add this whitelist to this bot',
            ],
        ];

        $validator = app('Swapbot\Http\Requests\Bot\Validators\CreateBotValidator');
        app('ValidatorHelper')->runTests($test_specs, $validator, [$user]);

        $validator = app('Swapbot\Http\Requests\Bot\Validators\UpdateBotValidator');
        app('ValidatorHelper')->runTests($test_specs, $validator, [$user]);
    }

    ////////////////////////////////////////////////////////////////////////
    
    


}
