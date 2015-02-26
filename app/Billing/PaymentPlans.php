<?php

namespace Swapbot\Billing;

use Exception;

/*
* PaymentPlans
*/
class PaymentPlans {

    public function __construct() {
    }

    /**
    * $1 creation fee + .001BTC fee per TX (22 cents) ($110 for 500 swaps)
    * $10 creation fee + .0005BTC fee per TX (10 cents) ($50 for 500 swaps)
    * $100 creation fee + .0001BTC fee per TX (2 cents) ($10 for 500 swaps)
    */
    public function allPaymentPlans() {
        return [
            'txfee001' => [
                'id'       => 'txfee001',
                'name'     => '0.005 BTC creation fee + .001 BTC per TX',
                'setupFee' => 0.005,
                'txFee'    => 0.001,
            ],
            'txfee002' => [
                'id'       => 'txfee002',
                'name'     => '0.05 BTC creation fee + .0005 BTC per TX',
                'setupFee' => 0.05,
                'txFee'    => 0.0005,
            ],
            'txfee003' => [
                'id'       => 'txfee003',
                'name'     => '0.5 BTC creation fee + .0001 BTC per TX',
                'setupFee' => 0.5,
                'txFee'    => 0.0001,
            ],
        ];
    }

}


