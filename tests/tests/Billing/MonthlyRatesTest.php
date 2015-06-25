<?php

use Swapbot\Swap\Settings\Facade\Settings;
use \PHPUnit_Framework_Assert as PHPUnit;

class MonthlyRatesTest extends TestCase {

    protected $use_database = true;

    public function testMonthlyRates()
    {
        // mock quotebot
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $payment_plans = app('Swapbot\Billing\PaymentPlans');
        $rates = $payment_plans->getMonthlyRates('monthly001');

        // tokenly
        PHPUnit::assertEquals(1, $rates['tokenly']['quantity']);
        PHPUnit::assertEquals('TOKENLY', $rates['tokenly']['asset']);

        // check btc/fiat
        PHPUnit::assertEquals(0.035, $rates['btc']['quantity']);
    }


    public function testConfiguredMonthlyRates()
    {
        // mock quotebot
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        // set settings
        app('SettingHelper')->newSampleSetting([
            'name'  => 'rates:monthly001',
            'value' => [
                'tokenly' => ['asset' => 'TOKENLY', 'quantity' => 2,      'strategy' => 'fixed', ],
                'ltbcoin' => ['asset' => 'LTBCOIN', 'quantity' => 30000,  'strategy' => 'fixed', ],
                'btc'     => ['asset' => 'BTC',     'fiatAmount' => 10.00, 'strategy' => 'fiat',  ],
            ],
        ]);

        $payment_plans = app('Swapbot\Billing\PaymentPlans');
        $rates = $payment_plans->getMonthlyRates('monthly001');

        // tokenly
        PHPUnit::assertEquals(2, $rates['tokenly']['quantity']);
        PHPUnit::assertEquals('TOKENLY', $rates['tokenly']['asset']);
        PHPUnit::assertEquals(30000, $rates['ltbcoin']['quantity']);

        // check btc/fiat
        PHPUnit::assertEquals(0.05, $rates['btc']['quantity']);
    }

    public function testUpdatedConfiguredMonthlyRatesClearsCache()
    {
        // mock quotebot
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $payment_plans = app('Swapbot\Billing\PaymentPlans');
        $rates = $payment_plans->getMonthlyRates('monthly001');
        PHPUnit::assertEquals(1, $rates['tokenly']['quantity']);


        // change settings
        app('SettingHelper')->newSampleSetting([
            'name'  => 'rates:monthly001',
            'value' => [
                'tokenly' => ['asset' => 'TOKENLY', 'quantity' => 2,      'strategy' => 'fixed', ],
                'ltbcoin' => ['asset' => 'LTBCOIN', 'quantity' => 30000,  'strategy' => 'fixed', ],
                'btc'     => ['asset' => 'BTC',     'fiatAmount' => 10.00, 'strategy' => 'fiat',  ],
            ],
        ]);
        $rates = $payment_plans->getMonthlyRates('monthly001');

        // tokenly
        PHPUnit::assertEquals(2, $rates['tokenly']['quantity']);


        // update settings
        Settings::put('rates:monthly001', [
            'tokenly' => ['asset' => 'TOKENLY', 'quantity' => 3,      'strategy' => 'fixed', ],
        ]);
        $rates = $payment_plans->getMonthlyRates('monthly001');

        // tokenly
        PHPUnit::assertEquals(3, $rates['tokenly']['quantity']);


        // delete settings
        Settings::delete('rates:monthly001');
        $rates = $payment_plans->getMonthlyRates('monthly001');

        // tokenly
        PHPUnit::assertEquals(1, $rates['tokenly']['quantity']);
    }


    ////////////////////////////////////////////////////////////////////////
    
    


}
