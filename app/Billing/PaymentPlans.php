<?php

namespace Swapbot\Billing;

use Exception;
use Illuminate\Contracts\Cache\Repository;
use Swapbot\Swap\Settings\Facade\Settings;
use Tokenly\QuotebotClient\Client;

/*
* PaymentPlans
*/
class PaymentPlans {

    public function __construct(Client $quotebot_client, Repository $cache_store) {
        $this->quotebot_client = $quotebot_client;
        $this->cache_store     = $cache_store;
    }

    public function allPaymentPlanNames() {
        return ['monthly001'];
    }

    public function allPaymentPlans() {
        return [
            'monthly001' => [
                'id'           => 'monthly001',
                'type'         => 'monthly',
                'name'         => 'Monthly SwapBot Rental',
                'setupFee'     => 0,
                'txFee'        => 0,
                'monthlyRates' => $this->getMonthlyRates('monthly001'),
            ],

        ];
    }

    public function getMonthlyRates($rate_name) {
        // check cache
        $cache_monthly_rates = $this->cache_store->get('billing.monthly_rates.'.$rate_name);
        if ($cache_monthly_rates !== null AND $cache_monthly_rates) { return $cache_monthly_rates; }

        // build rates
        $default_rates = [
            'btc'     => ['asset' => 'BTC',     'fiatAmount' => 7.00, 'strategy' => 'fiat',  ],
            'tokenly' => ['asset' => 'TOKENLY', 'quantity' => 1,      'strategy' => 'fixed', ],
            'ltbcoin' => ['asset' => 'LTBCOIN', 'quantity' => 60000,  'strategy' => 'fixed', ],
        ];

        // apply settings if they exist
        $setting_rates = Settings::get('rates:'.$rate_name);
        if ($setting_rates) {
            $default_rates = [];
            foreach($setting_rates as $rate_id => $rate_info) {
                $default_rates[$rate_id] = $rate_info;
            }
        }

        // resolve fiat rates
        $rates = [];
        foreach($default_rates as $rate_id => $rate_info) {
            if (isset($rate_info['strategy']) AND $rate_info['strategy'] == 'fiat') {
                // BTC only
                if ($rate_info['asset'] != 'BTC') { throw new Exception("Only BTC is supported for fiat based rates", 1); }

                $fiat_rate_info = ['asset' => $rate_info['asset'], 'quantity' => 0, 'strategy' => $rate_info['strategy'], 'fiatAmount' => $rate_info['fiatAmount'], ];
                $quote_entry = $this->quotebot_client->getQuote('bitcoinAverage', ['USD', 'BTC']);

                $quantity = $rate_info['fiatAmount'] / $quote_entry['last'];
                $fiat_rate_info['quantity'] = $quantity;
                $fiat_rate_info['description'] = round($quantity, 8).' ($'.money_format('%i', $rate_info['fiatAmount']).') in '.$rate_info['asset'];
                $rates[$rate_id] = $fiat_rate_info;
            } else {
                // pass through
                $rate_info['description'] = $rate_info['quantity'].' '.$rate_info['asset'];
                $rates[$rate_id] = $rate_info;
            }
        }

        // cache for 15 min
        $this->cache_store->put('billing.monthly_rates.'.$rate_name, $rates, 15);

        return $rates;
    }

    public function clearCacheForPaymentSettingsUpdated($setting) {
        $rate_name = substr($setting['name'], 6);
        $this->cache_store->forget('billing.monthly_rates.'.$rate_name);
    }

    public function subscribe($events) {
        $events->listen('settings.*.rates:monthly*', 'Swapbot\Billing\PaymentPlans@clearCacheForPaymentSettingsUpdated');
    }

}


