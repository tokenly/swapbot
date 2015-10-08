<?php

use Swapbot\Models\Data\SwapConfig;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotStrategyTest extends TestCase {

    protected $use_database = true;

    public function testSwapRateStrategies()
    {
        $m = function($a, $b) { return array_merge($a, $b); };

        $base_swap = [
            'in'        => 'LTBCOIN',
            'out'       => 'BTC',
            'strategy'  => 'rate',
            'rate'      => 0.00000150,
            'min'       => 0,
            'direction' => SwapConfig::DIRECTION_SELL,
        ];
        $base_receipt = [
            'quantityIn'  => 1000,
            'assetIn'     => 'LTBCOIN',
            'quantityOut' => 0.0015,
            'assetOut'    => 'BTC',
        ];
        $bot = null;

        $this->runStrategyTest($bot, $m($base_swap, []), 1000, $m($base_receipt, []), false);
        $this->runStrategyTest($bot, $m($base_swap, ['min' => 1001]), 1000, $m($base_receipt, []), true);

        // selling 1 BTC for 3 LTBCOIN
        $this->runStrategyTest($bot, $m($base_swap, ['rate' => 1/3]), 3, $m($base_receipt, ['quantityIn' => 3, 'quantityOut' => 1]), false);

        // selling 1 BTC for 300000 LTBCOIN
        $this->runStrategyTest($bot, $m($base_swap, ['rate' => 1/300000]), 1000, $m($base_receipt, ['quantityOut' => 0.00333333]), false);

        // selling 1 BTC for 300000000 LTBCOIN
        $this->runStrategyTest($bot, $m($base_swap, ['rate' => 1/300000000]), 1000, $m($base_receipt, ['quantityOut' => 0.00000333]), false);

        // selling 1 BTC for 30,000,000,000 LTBCOIN
        $this->runStrategyTest($bot, $m($base_swap, ['rate' => 1/30000000000]), 1000, $m($base_receipt, ['quantityOut' => 0.00000003]), false);

    }

    public function testSwapBulkDiscountStrategies()
    {
        $m = function($a, $b) { return array_merge($a, $b); };

        $base_swap = [
            'in'            => 'LTBCOIN',
            'out'           => 'BTC',
            'strategy'      => 'rate',
            'rate'          => 0.00000100,
            'min'           => 0,
            'direction'     => SwapConfig::DIRECTION_SELL,
            'swap_rule_ids' => ['rule0001'],
        ];
        $base_receipt = [
            'quantityIn'  => 5000,
            'assetIn'     => 'LTBCOIN',
            'quantityOut' => 0.0050,
            'assetOut'    => 'BTC',
        ];
        $swap_rules = [
            [
                'uuid'      => 'rule0001',
                'name'      => 'My Bulk Discount',
                'ruleType'  => 'bulkDiscount',
                'discounts' => [
                    [
                        'moq' => 0.00800000,
                        'pct' => 0.01
                    ],
                    [
                        'moq' => 0.00900000,
                        'pct' => 0.05
                    ],
                    [
                        'moq' => 0.01000000,
                        'pct' => 0.10
                    ],
                    [   // reversed order
                        'moq' => 0.02000000,
                        'pct' => 0.15
                    ],
                ]
            ]
        ];

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug(null, ['swap_rules' => $swap_rules]);

        // 5000 LTBCOIN => 0.005 BTC (no discount)
        $this->runStrategyTest($bot, $m($base_swap, []), 5000, $m($base_receipt, []), false);

        // 7920 LTBCOIN => 0.008 BTC (discount 1%)
        $this->runStrategyTest($bot, $m($base_swap, []), 7920, $m($base_receipt, ['quantityIn' => 7920, 'quantityOut' => 0.008, 'originalQuantityOut' => 0.007920]), false);

        // 8550 LTBCOIN => 0.009 BTC (discount 5%)
        $this->runStrategyTest($bot, $m($base_swap, []), 8550, $m($base_receipt, ['quantityIn' => 8550, 'quantityOut' => 0.009, 'originalQuantityOut' => 0.008550]), false);

        // 9000 LTBCOIN => 0.010 BTC (discount 10%)
        $this->runStrategyTest($bot, $m($base_swap, []), 9000, $m($base_receipt, ['quantityIn' => 9000, 'quantityOut' => 0.010000, 'originalQuantityOut' => 0.009000]), false);

        // 13500 LTBCOIN => 0.015 BTC (discount 10%)
        $this->runStrategyTest($bot, $m($base_swap, []), 13500, $m($base_receipt, ['quantityIn' => 13500, 'quantityOut' => 0.015000, 'originalQuantityOut' => 0.013500]), false);

        // 17000 LTBCOIN => 0.020 BTC (discount 15%)
        $this->runStrategyTest($bot, $m($base_swap, []), 17000, $m($base_receipt, ['quantityIn' => 17000, 'quantityOut' => 0.020000, 'originalQuantityOut' => 0.017000]), false);

        // 34000 LTBCOIN => 0.040 BTC (discount 15%)
        $this->runStrategyTest($bot, $m($base_swap, []), 34000, $m($base_receipt, ['quantityIn' => 34000, 'quantityOut' => 0.040000, 'originalQuantityOut' => 0.034000]), false);



    }

    public function testMultipleSwapBulkDiscountStrategies()
    {
        $m = function($a, $b) { return array_merge($a, $b); };

        $base_swap = [
            'in'            => 'LTBCOIN',
            'out'           => 'BTC',
            'strategy'      => 'rate',
            'rate'          => 0.00000100,
            'min'           => 0,
            'direction'     => SwapConfig::DIRECTION_SELL,
            'swap_rule_ids' => ['rule0001','rule0002',],
        ];
        $base_receipt = [
            'quantityIn'  => 5000,
            'assetIn'     => 'LTBCOIN',
            'quantityOut' => 0.0050,
            'assetOut'    => 'BTC',
        ];
        $swap_rules = [
            [
                'uuid'      => 'rule0001',
                'name'      => 'My Bulk Discount',
                'ruleType'  => 'bulkDiscount',
                'discounts' => [
                    [
                        'moq' => 0.01000000,
                        'pct' => 0.10
                    ],
                ]
            ],
            [
                'uuid'      => 'rule0002',
                'name'      => 'My Bulk Discount Two',
                'ruleType'  => 'bulkDiscount',
                'discounts' => [
                    [
                        'moq' => 0.02000000,
                        'pct' => 0.15
                    ],
                ]
            ],
        ];

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug(null, ['swap_rules' => $swap_rules]);

        // 5000 LTBCOIN => 0.005 BTC (no discount)
        $this->runStrategyTest($bot, $m($base_swap, []), 5000, $m($base_receipt, []), false);

        // 9000 LTBCOIN => 0.010 BTC (discount 10%)
        $this->runStrategyTest($bot, $m($base_swap, []), 9000, $m($base_receipt, ['quantityIn' => 9000, 'quantityOut' => 0.010000, 'originalQuantityOut' => 0.009000]), false);

        // 17000 LTBCOIN => 0.020 BTC (discount 15%)
        $this->runStrategyTest($bot, $m($base_swap, []), 17000, $m($base_receipt, ['quantityIn' => 17000, 'quantityOut' => 0.020000, 'originalQuantityOut' => 0.017000]), false);

        // 34000 LTBCOIN => 0.040 BTC (discount 15%)
        $this->runStrategyTest($bot, $m($base_swap, []), 34000, $m($base_receipt, ['quantityIn' => 34000, 'quantityOut' => 0.040000, 'originalQuantityOut' => 0.034000]), false);

    }

    public function testAllFixedBulkDiscountStrategies()
    {
        $m = function($a, $b) { return array_merge($a, $b); };

        $base_swap = [
            'in'            => 'BTC',
            'out'           => 'LTBCOIN',
            'strategy'      => 'fixed',
            'in_qty'        => 0.5,
            'out_qty'       => 50000,
            'direction'     => SwapConfig::DIRECTION_SELL,
            'swap_rule_ids' => ['rule0001'],
        ];
        $base_receipt = [
            'quantityIn'  => 0.5,
            'assetIn'     => 'BTC',
            'quantityOut' => 50000,
            'assetOut'    => 'LTBCOIN',
        ];
        $swap_rules = [
            [
                'uuid'      => 'rule0001',
                'name'      => 'My Bulk Discount',
                'ruleType'  => 'bulkDiscount',
                'discounts' => [
                    [
                        'moq' => 100000,
                        'pct' => 0.05
                    ],
                ]
            ]
        ];

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug(null, ['swap_rules' => $swap_rules]);

        // 0.5 BTC => 50000 LTBCOIN
        $this->runStrategyTest($bot, $m($base_swap, []), 0.5, $m($base_receipt, []), false);

        // 0.95 BTC => 100000 LTBCOIN
        $this->runStrategyTest($bot, $m($base_swap, []), 0.95, $m($base_receipt, ['quantityIn' => 0.95, 'quantityOut' => 100000, 'originalQuantityOut' => 95000]), false);

        // 1.9  BTC => 200000 LTBCOIN
        $this->runStrategyTest($bot, $m($base_swap, []),  1.9, $m($base_receipt, ['quantityIn' =>  1.9, 'quantityOut' => 200000, 'originalQuantityOut' => 190000]), false);

    }

    public function testFiatBulkDiscountStrategies()
    {
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();
        $m = function($a, $b) { return array_merge($a, $b); };


        $base_swap = [
            'in'            => 'BTC',
            'out'           => 'TOKENLY',
            'strategy'      => 'fiat',
            'fiat'          => 'USD',
            'source'        => 'bitcoinAverage',
            'divisible'     => false,
            'min_out'       => 0,
            'cost'          => 10.00,
            'direction'     => SwapConfig::DIRECTION_SELL,
            'swap_rule_ids' => ['rule0001'],
        ];

        $base_receipt = [
            'quantityIn'          => 0.5,
            'assetIn'             => 'BTC',
            'quantityOut'         => 10,
            'assetOut'            => 'TOKENLY',
            'changeOut'           => 0,
            'conversionRate'      => 200.0,
            'originalQuantityOut' => null,
        ];

        $swap_rules = [
            [
                'uuid'      => 'rule0001',
                'name'      => 'My Bulk Discount',
                'ruleType'  => 'bulkDiscount',
                'discounts' => [
                    [
                        'moq' => 20,
                        'pct' => 0.05
                    ],
                ]
            ]
        ];

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug(null, ['swap_rules' => $swap_rules]);

        // 0.5 BTC => 10 TOKENLY
        $this->runStrategyTest($bot, $m($base_swap, []), 0.5, $m($base_receipt, []), false);

        // 0.501 BTC => 10 TOKENLY
        $this->runStrategyTest($bot, $m($base_swap, []), 0.501, $m($base_receipt, ['quantityIn' => 0.501, 'changeOut' => 0.001]), false);

        // 0.95 BTC => 20 TOKENLY
        $this->runStrategyTest($bot, $m($base_swap, []), 0.95, $m($base_receipt, ['quantityIn' => 0.95, 'quantityOut' => 20, 'originalQuantityOut' => 19]), false);

    }

    ////////////////////////////////////////////////////////////////////////
    
    protected function runStrategyTest($bot, $config_data, $quantity_in, $expected_receipt_values, $expected_should_refund=false) {
        $swap_config = new SwapConfig($config_data);

        if ($bot) { $swap_rules = $swap_config->buildAppliedSwapRules($bot['swap_rules']); }
            else { $swap_rules = []; }

        $strategy = $swap_config->getStrategy();
        $actual_receipt_values = $strategy->calculateInitialReceiptValues($swap_config, $quantity_in, $swap_rules);
        $actual_should_refund = $strategy->shouldRefundTransaction($swap_config, $quantity_in, $swap_rules);

        // compare
        PHPUnit::assertEquals($expected_receipt_values, $actual_receipt_values);
        if ($expected_should_refund) {
            PHPUnit::assertTrue($actual_should_refund, "Mismatched refund calculation");
        } else {
            PHPUnit::assertFalse($actual_should_refund, "Mismatched refund calculation");
        }
    }


}
