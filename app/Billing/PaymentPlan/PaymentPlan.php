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

    public function getCreationFee() {
        return $this['setupFee'];
    }

    public function getTXFee() {
        return $this['txFee'];
    }

    public function isMonthly() {
        return ($this['type'] == 'monthly');
    }

}


