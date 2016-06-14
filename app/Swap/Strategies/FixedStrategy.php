<?php

namespace Swapbot\Swap\Strategies;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\RefundConfig;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Swap\Contracts\Strategy;
use Swapbot\Swap\Rules\SwapRuleHandler;
use Swapbot\Swap\Strategies\StrategyHelpers;

class FixedStrategy implements Strategy {

    function __construct(SwapRuleHandler $swap_rule_handler) {
        $this->swap_rule_handler = $swap_rule_handler;
    }

    public function shouldRefundTransaction(SwapConfig $swap_config, $quantity_in, $swap_rules=[], $receipt_vars=null) {
        $swap_vars = $this->calculateInitialReceiptValues($swap_config, $quantity_in, $swap_rules);

        // never try to send 0 of an asset
        if ($swap_vars['quantityOut'] <= 0) { return true; }

        return false;
    }

    public function buildRefundReason(SwapConfig $swap_config, $quantity_in) {
        return RefundConfig::REASON_BELOW_MINIMUM;
    }

    public function calculateInitialReceiptValues(SwapConfig $swap_config, $quantity_in, $swap_rules=[]) {
        // build trial receipt
        $receipt_values = [
            'quantityIn'  => $quantity_in,
            'assetIn'     => $swap_config['in'],

            'quantityOut' => 0,
            'assetOut'    => $swap_config['out'],
        ];

        // build the standard quantity out (with no rules)
        $quantity_out = floor(bcdiv($quantity_in, $swap_config['in_qty'])) * $swap_config['out_qty'];
        $receipt_values['quantityOut'] = round($quantity_out, 8);

        // build a quantity_out without rounding down in order to calculate the discount
        $unfloored_quantity_out = $quantity_in / $swap_config['in_qty'] * $swap_config['out_qty'];

        // execute the rule engine
        $modified_quantity_out = $this->swap_rule_handler->modifyInitialQuantityOut($unfloored_quantity_out, $swap_rules, $quantity_in, $swap_config);
        if ($modified_quantity_out !== null) {
            $receipt_values['originalQuantityOut'] = $unfloored_quantity_out;

            // round down to the exact multiple again
            //  and use that to recalculate the multiplier
            $multiplier = floor(bcdiv($modified_quantity_out, $swap_config['out_qty']));
            $receipt_values['quantityOut'] = round($multiplier * $swap_config['out_qty'], 8);
        }

        return $receipt_values;
    }
    
    public function unSerializeDataToSwap($data, SwapConfig $swap_config) {
        // strategy is already set

        $swap_config['in']            = isset($data['in'])            ? $data['in']            : null;
        $swap_config['out']           = isset($data['out'])           ? $data['out']           : null;
        $swap_config['in_qty']        = isset($data['in_qty'])        ? $data['in_qty']        : null;
        $swap_config['out_qty']       = isset($data['out_qty'])       ? $data['out_qty']       : null;
        $swap_config['direction']     = isset($data['direction'])     ? $data['direction']     : SwapConfig::DIRECTION_SELL;
        $swap_config['swap_rule_ids'] = isset($data['swap_rule_ids']) ? $data['swap_rule_ids'] : null;

        // // minimum purchase
        // $swap_config['min']     = isset($data['min']) ? $data['min'] : 0;
    }

    public function serializeSwap(SwapConfig $swap_config) {
        return [
            'strategy'      => $swap_config['strategy'],
            'direction'     => ($swap_config['direction'] == SwapConfig::DIRECTION_BUY) ? SwapConfig::DIRECTION_BUY : SwapConfig::DIRECTION_SELL,
            'in'            => $swap_config['in'],
            'out'           => $swap_config['out'],
            'in_qty'        => $swap_config['in_qty'],
            'out_qty'       => $swap_config['out_qty'],
            // 'min'        => $swap_config['min'],
            'swap_rule_ids' => $swap_config['swap_rule_ids'],
        ];
    }

    public function validateSwap($swap_number, $swap_config, MessageBag $errors) {
        $in_value      = isset($swap_config['in'])      ? $swap_config['in']      : null;
        $out_value     = isset($swap_config['out'])     ? $swap_config['out']     : null;
        $in_qty_value  = isset($swap_config['in_qty'])  ? $swap_config['in_qty']  : null;
        $out_qty_value = isset($swap_config['out_qty']) ? $swap_config['out_qty'] : null;
        // $min_value     = isset($swap_config['min'])     ? $swap_config['min']     : null;
        $direction_value = isset($swap_config['direction']) ? $swap_config['direction'] : SwapConfig::DIRECTION_SELL;

        $exists = (strlen($in_value) OR strlen($out_value) OR strlen($in_qty_value) OR strlen($out_qty_value));
        if ($exists) {
            $assets_are_valid = true;

            // direction
            if ($direction_value != SwapConfig::DIRECTION_SELL AND $direction_value != SwapConfig::DIRECTION_BUY) {
                $errors->add('direction', "Please specify a valid direction for swap #{$swap_number}");
            }

            // in and out assets
            if (!StrategyHelpers::validateAssetName($in_value, 'receive', $swap_number, 'in', $errors)) { $assets_are_valid = false; }
            if (!StrategyHelpers::validateAssetName($out_value, 'send', $swap_number, 'out', $errors)) { $assets_are_valid = false; }

            // in and out qty
            StrategyHelpers::validateQuantity($in_qty_value, 'receive', $swap_number, 'in_qty', $errors);
            StrategyHelpers::validateQuantity($out_qty_value, 'send', $swap_number, 'out_qty', $errors);

            // make sure assets aren't the same
            if ($assets_are_valid AND $in_value == $out_value) {
                $errors->add('rate', "The assets to receive and send for swap #{$swap_number} should not be the same.");
            }
        } else {
            $errors->add('swaps', "The values specified for swap #{$swap_number} were not valid.");
        }
    }

    public function validateSwapRuleConfig($swap_rule, MessageBag $errors) {
    }

    ////////////////////////////////////////////////////////////////////////
    // Index

    public function buildIndexEntries(SwapConfig $swap_config) {
        return [
            [
                'in'   => $swap_config['in'],
                'out'  => $swap_config['out'],
                // 'rate' => ($swap_config['out_qty'] / $swap_config['in_qty']),
                'cost' => ($swap_config['in_qty'] / $swap_config['out_qty']),
            ]
        ];
    }

    public function buildSwapDetailsForAPI(SwapConfig $swap_config, $in=null) {
        return [
            'in'      => $swap_config['in'],
            'out'     => $swap_config['out'],
            'rate'    => ($swap_config['out_qty'] / $swap_config['in_qty']),
            'cost'    => ($swap_config['in_qty'] / $swap_config['out_qty']),

            'inQuantity'  => $swap_config['in_qty'],
            'outQuantity' => $swap_config['out_qty'],
        ];
    }

}
