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
            foreach($swap_rule_configs as $swap_rule_config) {
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

}
