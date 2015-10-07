<?php

namespace Swapbot\Models\Data;

use ArrayObject;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

class SwapRuleConfig extends ArrayObject implements APISerializeable {

    protected $strategy_obj = null;

    function __construct($data=[]) {
        parent::__construct($data);
    }

    public static function createFromSerialized($data) {
        $swap = new SwapRuleConfig();
        $swap->unserialize($data);
        return $swap;
    }


    // receives from the API and unserializes to memory
    public function unserialize($data) {
        $this['uuid']     = isset($data['uuid'])     ? $data['uuid']     : null;
        $this['name']     = isset($data['name'])     ? $data['name']     : null;
        $this['ruleType'] = isset($data['ruleType']) ? $data['ruleType'] : null;

        // call unserializeFromAPI_bulkDiscount
        $method = "unserializeFromAPI_{$this['ruleType']}";
        if (method_exists($this, $method)) {
            call_user_func([$this, $method], $data);
        }

        return $this;
    }

    // serializes from memory to go out to the API
    public function serialize() {
        $out = [
            'uuid'     => $this['uuid'],
            'name'     => $this['name'],
            'ruleType' => $this['ruleType'],
        ];

        // call serializeForAPI_bulkDiscount
        $method = "serializeForAPI_{$this['ruleType']}";
        if (method_exists($this, $method)) {
            $out = call_user_func([$this, $method], $out);
        }

        return $out;
    }

    public function serializeForAPI() { return $this->serialize(); }

    public function isEmpty() {
        return (
            !strlen($this['name'])
            AND !strlen($this['ruleType'])
        );
    }


    // ---------------------------------------------------------------

    protected function serializeForAPI_bulkDiscount($out) {
        $out['discounts'] = isset($this['discounts']) ? $this['discounts'] : [];
        return $out;
    }

    protected function unserializeFromAPI_bulkDiscount($data) {
        $raw_discounts = (isset($data['discounts']) AND is_array($data['discounts'])) ? $data['discounts'] : [];
        $discounts = [];
        foreach($raw_discounts as $raw_discount) {
            if (strlen($raw_discount['moq']) OR strlen($raw_discount['pct'])) {
                $discounts[] = $raw_discount;
            }
        }

        $this['discounts'] = $discounts;

        return null;
    }

}
