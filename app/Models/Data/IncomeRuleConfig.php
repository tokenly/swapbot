<?php

namespace Swapbot\Models\Data;

use ArrayObject;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

class IncomeRuleConfig extends ArrayObject implements APISerializeable {

    protected $strategy_obj = null;

    function __construct($data=[]) {
        parent::__construct($data);
    }

    public static function createFromSerialized($data) {
        $swap = new IncomeRuleConfig();
        $swap->unSerialize($data);
        return $swap;
    }

    public function unSerialize($data) {
        $this['asset']         = isset($data['asset'])         ? $data['asset']         : null;
        $this['minThreshold']  = isset($data['minThreshold'])  ? $data['minThreshold']  : null;
        $this['paymentAmount'] = isset($data['paymentAmount']) ? $data['paymentAmount'] : null;
        $this['address']       = isset($data['address'])       ? $data['address']       : null;

        return $this;
    }

    public function serialize() {
        return [
            'asset'         => $this['asset'],
            'minThreshold'  => $this['minThreshold'],
            'paymentAmount' => $this['paymentAmount'],
            'address'       => $this['address'],
        ];
    }

    public function serializeForAPI() { return $this->serialize(); }

    public function isEmpty() {
        return (
            !strlen($this['asset'])
            AND !strlen($this['minThreshold'])
            AND !strlen($this['paymentAmount'])
            AND !strlen($this['address'])
        );
    }


}
