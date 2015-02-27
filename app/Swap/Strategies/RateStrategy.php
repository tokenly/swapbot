<?php

namespace Swapbot\Swap\Strategies;

use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Swap\Contracts\Strategy;
use Swapbot\Swap\Strategies\StrategyHelpers;

class RateStrategy implements Strategy {

    public function buildSwapOutputQuantityAndAsset($swap, $in_quantity) {
        $quantity = $in_quantity * $swap['rate'];
        $asset = $swap['out'];

        return [$quantity, $asset];
    }

    public function unSerializeDataToSwap($data, SwapConfig $swap) {
        // strategy is already set

        $swap['in']   = isset($data['in'])   ? $data['in']   : null;
        $swap['out']  = isset($data['out'])  ? $data['out']  : null;
        $swap['rate'] = isset($data['rate']) ? $data['rate'] : null;
    }

    public function serializeSwap(SwapConfig $swap) {
        return [
            'strategy' => $swap['strategy'],
            'in'       => $swap['in'],
            'out'      => $swap['out'],
            'rate'     => $swap['rate'],
        ];
    }

    public function validateSwap($swap_number, $swap, MessageBag $errors) {
        $in_value   = isset($swap['in'])   ? $swap['in']   : null;
        $out_value  = isset($swap['out'])  ? $swap['out']  : null;
        $rate_value = isset($swap['rate']) ? $swap['rate'] : null;

        $exists = (strlen($in_value) OR strlen($out_value) OR strlen($rate_value));
        if ($exists) {
            $assets_are_valid = true;

            // in and out assets
            if (!StrategyHelpers::validateAssetName($in_value, 'receive', $swap_number, 'in', $errors)) { $assets_are_valid = false; }
            if (!StrategyHelpers::validateAssetName($out_value, 'send', $swap_number, 'out', $errors)) { $assets_are_valid = false; }

            // rate
            if (strlen($rate_value)) {
                if (!StrategyHelpers::isValidRate($rate_value)) {
                    $errors->add('rate', "The rate for swap #{$swap_number} was not valid.");
                }
            } else {
                $errors->add('rate', "Please specify a valid rate for swap #{$swap_number}");
            }

            // make sure assets aren't the same
            if ($assets_are_valid AND $in_value == $out_value) {
                $errors->add('rate', "The assets to receive and send for swap #{$swap_number} should not be the same.");
            }
        } else {
            $errors->add('swaps', "The values specified for swap #{$swap_number} were not valid.");
        }
    }

}
