<?php

namespace Swapbot\Models\Data;

use ArrayObject;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

class SwapConfig extends ArrayObject implements APISerializeable {

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
        if (!isset($data['strategy']) AND !isset($data['in']) AND isset($data[0])) { return $this->unSerializeLegacyData($data); }

        // strategy
        $strategy_type = isset($data['strategy']) ? $data['strategy'] : 'rate';
        $this['strategy'] = $strategy_type;
        
        $strategy = app('Swapbot\Swap\Factory\StrategyFactory')->newStrategy($strategy_type);
        $strategy->unSerializeDataToSwap($data, $this);

        return $this;
    }

    public function serialize() {
        $strategy_type = $this['strategy'];
        $strategy = app('Swapbot\Swap\Factory\StrategyFactory')->newStrategy($strategy_type);
        return $strategy->serializeSwap($this);
    }

    public function serializeForAPI() { return $this->serialize(); }



    protected function unSerializeLegacyData($data) {
        $this['in']       = $data[0];
        $this['out']      = $data[1];
        $this['strategy'] = 'rate';
        $this['rate']     = $data[2];
        return $this;
    }

}
