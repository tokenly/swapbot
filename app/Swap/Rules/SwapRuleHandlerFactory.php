<?php

namespace Swapbot\Swap\Rules;

use Exception;
use Illuminate\Foundation\Application;
use Swapbot\Models\Data\SwapRuleConfig;

class SwapRuleHandlerFactory {

    /**
     * Create the command handler.
     *
     * @return void
     */
    function __construct(Application $app) {
        $this->app = $app;
    }


    public function isValidRuleType($type) {
        switch (lcfirst($type)) {
            case 'bulkDiscount': return true;
        }

        return false;
    }

    public function newRuleHandler(SwapRuleConfig $swap_rule_config) {
        $type = $swap_rule_config['ruleType'];
        if (!$this->isValidRuleType($type)) { throw new Exception("$type is an invalid rule type", 1); }
        $class = "Swapbot\\Swap\\Rules\\".ucfirst($type)."SwapRuleHandler";
        $rule_handler = $this->app->make($class);
        $rule_handler->setSwapRuleConfig($swap_rule_config);
        return $rule_handler;
    }

    protected function buildSwapRuleHandler(SwapRuleConfig $swap_rule_config) {
        $rule_type = $swap_rule_config['ruleType'];
        $class;
    }

}
