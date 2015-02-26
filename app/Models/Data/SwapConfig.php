<?php

namespace Swapbot\Models\Data;

use ArrayObject;
use Swapbot\Swap\Contracts\Strategy;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

class SwapConfig extends ArrayObject implements APISerializeable {

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
        // legacy conversion
        // if (!isset($data['strategy']) AND !isset($data['in']) AND isset($data[0])) { return $this->unSerializeLegacyData($data); }

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

    public function serializeForAPI() { return $this->serialize(); }


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

    // protected function unSerializeLegacyData($data) {
    //     $this['in']       = $data[0];
    //     $this['out']      = $data[1];
    //     $this['strategy'] = 'rate';
    //     $this['rate']     = $data[2];
    //     return $this;
    // }

}
