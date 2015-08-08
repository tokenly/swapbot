<?php

namespace Swapbot\Swap\Factory;

use Illuminate\Foundation\Application;

class StrategyFactory {

    function __construct(Application $app) {
        $this->app = $app;
    }

    public function isValidStrategyType($type) {
        switch (strtolower($type)) {
            case 'rate':
            case 'fixed':
            case 'fiat':
                return true;
                break;
        }

        return false;
    }

    public function newStrategy($type) {
        if (!$this->isValidStrategyType($type)) { throw new Exception("$type is an invalid strategy type", 1); }
        $class = "Swapbot\\Swap\\Strategies\\".ucwords($type)."Strategy";
        return $this->app->make($class);
    }


    public function getStrategy($type) {
        if (!isset($this->strategy_objects)) { $this->strategy_objects = []; }
        if (!isset($this->strategy_objects[$type])) {
            $this->strategy_objects[$type] = $this->newStrategy($type);
        }
        return $this->strategy_objects[$type];
    }


}
