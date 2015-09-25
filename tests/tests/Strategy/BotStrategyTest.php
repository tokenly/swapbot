<?php

use Swapbot\Models\Data\SwapConfig;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotStrategyTest extends TestCase {

    protected $use_database = false;

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

        $this->runStrategyTest($m($base_swap, []), 1000, $m($base_receipt, []), false);
        $this->runStrategyTest($m($base_swap, ['min' => 1001]), 1000, $m($base_receipt, []), true);

        // selling 1 BTC for 3 LTBCOIN
        $this->runStrategyTest($m($base_swap, ['rate' => 1/3]), 3, $m($base_receipt, ['quantityIn' => 3, 'quantityOut' => 1]), false);

        // selling 1 BTC for 300000 LTBCOIN
        $this->runStrategyTest($m($base_swap, ['rate' => 1/300000]), 1000, $m($base_receipt, ['quantityOut' => 0.00333333]), false);

        // selling 1 BTC for 300000000 LTBCOIN
        $this->runStrategyTest($m($base_swap, ['rate' => 1/300000000]), 1000, $m($base_receipt, ['quantityOut' => 0.00000333]), false);

        // selling 1 BTC for 30,000,000,000 LTBCOIN
        $this->runStrategyTest($m($base_swap, ['rate' => 1/30000000000]), 1000, $m($base_receipt, ['quantityOut' => 0.00000003]), false);

    }

    ////////////////////////////////////////////////////////////////////////
    
    protected function runStrategyTest($config_data, $quantity_in, $expected_receipt_values, $expected_should_refund=false) {
        $swap_config = new SwapConfig($config_data);

        $strategy = $swap_config->getStrategy();
        $actual_receipt_values = $strategy->caculateInitialReceiptValues($swap_config, $quantity_in);
        $actual_should_refund = $strategy->shouldRefundTransaction($swap_config, $quantity_in);

        // compare
        PHPUnit::assertEquals($expected_receipt_values, $actual_receipt_values);
        if ($expected_should_refund) {
            PHPUnit::assertTrue($actual_should_refund, "Mismatched refund calculation");
        } else {
            PHPUnit::assertFalse($actual_should_refund, "Mismatched refund calculation");
        }
    }


}
