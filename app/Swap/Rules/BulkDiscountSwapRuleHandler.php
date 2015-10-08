<?php

namespace Swapbot\Swap\Rules;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapRuleConfig;

class BulkDiscountSwapRuleHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct() {
    }

    public function setSwapRuleConfig(SwapRuleConfig $swap_rule_config) {
        $this->swap_rule_config = $swap_rule_config;
    }


    public function modifyInitialQuantityOut($quantity_out, $quantity_in, SwapConfig $swap_config) {
        // find the the highest discount
        $discount = $this->findBestDiscount($this->swap_rule_config, $quantity_out);
        if (!$discount) { return null; }

        return round(bcdiv($quantity_out, 1 - $discount['pct'], 8), 8);
    }

    protected function findBestDiscount($swap_rule_config, $quantity_out) {
        if (!isset($swap_rule_config['discounts'])) { return null; }
        $highest_matched_pct = null;
        $best_discount = null;

        foreach ($swap_rule_config['discounts'] as $discount) {
            if ($highest_matched_pct === null OR $discount['pct'] > $highest_matched_pct) {
                // Log::debug("\$discount['moq']={$discount['moq']} pct=".(1 - $discount['pct'])." ".bcmul($discount['moq'], (1 - $discount['pct']), 8)." <= \$quantity_out=$quantity_out  matched=".json_encode((bcmul($discount['moq'], (1 - $discount['pct']), 8) <= $quantity_out), 192));
                if (bcmul($discount['moq'], (1 - $discount['pct']), 8) <= round($quantity_out, 8)) {
                    $best_discount = $discount;
                    $highest_matched_pct = $discount['pct'];
                }
            }
        }

        return $best_discount;
    }

}
