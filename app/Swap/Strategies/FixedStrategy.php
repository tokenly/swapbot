<?php

namespace Swapbot\Swap\Strategies;

use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Swap\Contracts\Strategy;
use Swapbot\Swap\Strategies\StrategyHelpers;

class FixedStrategy implements Strategy {

    public function buildSwapOutputQuantityAndAsset($swap, $in_quantity) {
        $out_quantity = floor($in_quantity / $swap['in_qty']) * $swap['out_qty'];
        $asset = $swap['out'];

        return [$out_quantity, $asset];
    }
    
    public function unSerializeDataToSwap($data, SwapConfig $swap) {
        // strategy is already set

        $swap['in']      = isset($data['in'])   ? $data['in']   : null;
        $swap['out']     = isset($data['out'])  ? $data['out']  : null;
        $swap['in_qty']  = isset($data['in_qty']) ? $data['in_qty'] : null;
        $swap['out_qty'] = isset($data['out_qty']) ? $data['out_qty'] : null;
    }

    public function serializeSwap(SwapConfig $swap) {
        return [
            'strategy' => $swap['strategy'],
            'in'       => $swap['in'],
            'out'      => $swap['out'],
            'in_qty'   => $swap['in_qty'],
            'out_qty'  => $swap['out_qty'],
        ];
    }

    public function validateSwap($swap_number, $swap, MessageBag $errors) {
        $in_value      = isset($swap['in'])      ? $swap['in']      : null;
        $out_value     = isset($swap['out'])     ? $swap['out']     : null;
        $in_qty_value  = isset($swap['in_qty'])  ? $swap['in_qty']  : null;
        $out_qty_value = isset($swap['out_qty']) ? $swap['out_qty'] : null;

        $exists = (strlen($in_value) OR strlen($out_value) OR strlen($in_qty_value) OR strlen($out_qty_value));
        if ($exists) {
            $assets_are_valid = true;

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

}
