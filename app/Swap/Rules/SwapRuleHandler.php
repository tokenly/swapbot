<?php

namespace Swapbot\Swap\Rules;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Data\SwapConfig;

class SwapRuleHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(SwapRuleHandlerFactory $swap_rule_handler_factory) {
        $this->swap_rule_handler_factory = $swap_rule_handler_factory;
    }


    public function modifyInitialQuantityOut($quantity_out, $swap_rule_configs, $quantity_in, SwapConfig $swap_config) {
        // Log::debug("SwapRuleHandler modifyInitialQuantityOut swap_rule_configs=".json_encode($swap_rule_configs, 192));
        $final_quantity_out = null;
        $working_quantity_out = $quantity_out;

        if ($swap_rule_configs) {
            $aggregated_swap_rules = $this->aggregateSwapRuleConfigsByRuleType($swap_rule_configs);
            foreach($aggregated_swap_rules as $swap_rule_config) {
                $rule_handler = $this->swap_rule_handler_factory->newRuleHandler($swap_rule_config);

                // apply the rule
                $modified_working_quantity_out = $rule_handler->modifyInitialQuantityOut($working_quantity_out, $quantity_in, $swap_config);
                if ($modified_working_quantity_out !== null) {
                    $working_quantity_out = $modified_working_quantity_out;
                    $final_quantity_out = $modified_working_quantity_out;
                }
            }
        }

        return $final_quantity_out;
    }


    protected function aggregateSwapRuleConfigsByRuleType($swap_rule_configs) {
        $swap_rule_configs_by_type = [];
        foreach($swap_rule_configs as $swap_rule_config) {
            if (!isset($swap_rule_configs_by_type[$swap_rule_config['ruleType']])) { $swap_rule_configs_by_type[$swap_rule_config['ruleType']] = []; }
            $swap_rule_configs_by_type[$swap_rule_config['ruleType']][] = $swap_rule_config;
        }

        $aggregated_swap_rules = [];
        foreach($swap_rule_configs_by_type as $type => $swap_rule_configs_for_type) {
            $first_swap_rule_config = $swap_rule_configs_for_type[0];
            $rule_handler = $this->swap_rule_handler_factory->newRuleHandler($first_swap_rule_config);
            $aggregated_swap_rule_config = $rule_handler->aggregateSwapConfigs($swap_rule_configs_for_type);
            $aggregated_swap_rules[] = $aggregated_swap_rule_config;
        }

        return $aggregated_swap_rules;
    }
}
