<?php

namespace Swapbot\Models\Data;

use ArrayObject;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Data\SwapRuleConfig;
use Swapbot\Swap\Contracts\Strategy;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

class SwapConfig extends ArrayObject implements APISerializeable {

    const DIRECTION_SELL = 'sell';
    const DIRECTION_BUY  = 'buy';

    protected $strategy_obj = null;

    function __construct($data=[]) {
        parent::__construct($data);
    }

    public static function createFromSerialized($data) {
        $swap = new SwapConfig();
        $swap->unSerialize($data);
        return $swap;
    }

    public function unSerialize($data) {
        // set the strategy
        $strategy_type = isset($data['strategy']) ? $data['strategy'] : 'rate';
        $this['strategy'] = $strategy_type;
        
        // let the strategy unserialize the data
        $this->getStrategy()->unSerializeDataToSwap($data, $this);

        return $this;
    }

    public function serialize() {
        $strategy_type = $this['strategy'];
        $strategy = app('Swapbot\Swap\Factory\StrategyFactory')->newStrategy($strategy_type);
        return $strategy->serializeSwap($this);
    }

    public function serializeForAPI() {
        return $this->serialize();
    }

    public function serializeForAPIWithSwapRules($all_swap_rules) {
        $out = $this->serializeForAPI();
        $out['swapRules'] = $this->buildAppliedSwapRules($all_swap_rules);
        unset($out['swap_rule_ids']);
        return $out;
    }

    public function buildIndexEntries() { return $this->getStrategy()->buildIndexEntries($this); }


    public function buildName() {
        return $this['in'].':'.$this['out'];
    }

    public function getStrategy() {
        if (!isset($this->strategy_obj)) {
            $this->strategy_obj = app('Swapbot\Swap\Factory\StrategyFactory')->newStrategy($this['strategy']);
        }
        return $this->strategy_obj;
    }

    public function setStrategy(Strategy $strategy) {
        $this->strategy_obj = $strategy;
    }

    public function buildAppliedSwapRules($all_swap_rules) {
        $applied_swap_rules = [];
        if (isset($this['swap_rule_ids']) AND is_array($this['swap_rule_ids'])) {
            foreach ($this['swap_rule_ids'] as $applied_swap_rule_uuid) {
                foreach($all_swap_rules as $all_swap_rule) {
                    if ($all_swap_rule['uuid'] == $applied_swap_rule_uuid) {
                        $applied_swap_rules[] = SwapRuleConfig::createFromSerialized($all_swap_rule);
                    }
                }
            }
        }
        return $applied_swap_rules;
    }


}
