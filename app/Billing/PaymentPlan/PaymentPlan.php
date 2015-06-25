<?php

namespace Swapbot\Billing\PaymentPlan;


use ArrayObject;
use Exception;

/*
* PaymentPlan
*/
class PaymentPlan extends ArrayObject {

    function __construct($data=[]) {
        parent::__construct($data);
    }

    public function isMonthly() {
        return ($this['type'] == 'monthly');
    }

    public function calculateMonthlyPurchaseDetails($amount, $asset) {
        if (!$this->isMonthly()) { return false; }

        foreach ($this['monthlyRates'] as $_id => $rate_details) {
            if ($rate_details['asset'] == $asset AND $amount >= $rate_details['quantity']) {
                $months = floor($amount / $rate_details['quantity']);
                return [
                    'months' => $months,
                    'cost'   => $months * $rate_details['quantity'],
                    'asset'  => $asset,
                ];
            }
        }

        return false;
    }

    public function isAssetAccepted($asset) {
        // assumes monthly
        foreach ($this['monthlyRates'] as $_id => $rate_details) {
            if ($rate_details['asset'] == $asset) { return true; }
        }

        return false;
    }

}


