<?php

namespace Swapbot\Swap\Strategies;

use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\RefundConfig;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Swap\Contracts\Strategy;
use Swapbot\Swap\Strategies\StrategyHelpers;

class RateStrategy implements Strategy {

    public function shouldRefundTransaction(SwapConfig $swap_config, $quantity_in) {
        // if there is a minimum and the input is below this minimum
        //   then it should be refunded
        if ($quantity_in < $swap_config['min']) {
            return true;
        }

        $swap_vars = $this->caculateInitialReceiptValues($swap_config, $quantity_in);

        // never try to send 0 of an asset
        if ($swap_vars['quantityOut'] <= 0) { return true; }

        return false;
    }

    public function buildRefundReason(SwapConfig $swap_config, $quantity_in) {
        return RefundConfig::REASON_BELOW_MINIMUM;
    }


    public function caculateInitialReceiptValues(SwapConfig $swap_config, $quantity_in) {
        $quantity_out = $quantity_in * $swap_config['rate'];

        return [
            'quantityIn'  => $quantity_in,
            'assetIn'     => $swap_config['in'],

            'quantityOut' => $quantity_out,
            'assetOut'    => $swap_config['out'],
        ];
    }

    public function unSerializeDataToSwap($data, SwapConfig $swap_config) {
        // strategy is already set

        $swap_config['in']        = isset($data['in'])        ? $data['in']        : null;
        $swap_config['out']       = isset($data['out'])       ? $data['out']       : null;
        $swap_config['rate']      = isset($data['rate'])      ? $data['rate']      : null;
        $swap_config['min']       = isset($data['min'])       ? $data['min']       : 0;
        $swap_config['direction'] = isset($data['direction']) ? $data['direction'] : SwapConfig::DIRECTION_SELL;

        if ($swap_config['direction'] == SwapConfig::DIRECTION_SELL) {
            $swap_config['price'] = isset($data['price']) ? $data['price'] : null;
        } else {
            $swap_config['price'] = null;
        }
    }

    public function serializeSwap(SwapConfig $swap_config) {
        return [
            'strategy'  => $swap_config['strategy'],
            'direction' => ($swap_config['direction'] == SwapConfig::DIRECTION_BUY) ? SwapConfig::DIRECTION_BUY : SwapConfig::DIRECTION_SELL,
            'in'        => $swap_config['in'],
            'out'       => $swap_config['out'],
            'rate'      => $swap_config['rate'],
            'price'     => ($swap_config['direction'] == SwapConfig::DIRECTION_SELL ? $swap_config['price'] : null),
            'min'       => $swap_config['min'],
        ];
    }

    public function validateSwap($swap_number, $swap_config, MessageBag $errors) {
        $in_value        = isset($swap_config['in'])        ? $swap_config['in']        : null;
        $out_value       = isset($swap_config['out'])       ? $swap_config['out']       : null;
        $rate_value      = isset($swap_config['rate'])      ? $swap_config['rate']      : null;
        $price_value     = isset($swap_config['price'])     ? $swap_config['price']     : null;
        $min_value       = isset($swap_config['min'])       ? $swap_config['min']       : null;
        $direction_value = isset($swap_config['direction']) ? $swap_config['direction'] : SwapConfig::DIRECTION_SELL;

        $exists = (strlen($in_value) OR strlen($out_value) OR strlen($rate_value));
        if ($exists) {
            $assets_are_valid = true;

            // direction
            if ($direction_value != SwapConfig::DIRECTION_SELL AND $direction_value != SwapConfig::DIRECTION_BUY) {
                $errors->add('direction', "Please specify a valid direction for swap #{$swap_number}");
            }

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

            // rate
            if (strlen($rate_value)) {
                if (!StrategyHelpers::isValidRate($rate_value)) {
                    $errors->add('rate', "The rate for swap #{$swap_number} was not valid.");
                }
            } else {
                $errors->add('rate', "Please specify a valid rate for swap #{$swap_number}");
            }

            // min
            if (strlen($min_value)) {
                if (!StrategyHelpers::isValidQuantityOrZero($min_value)) {
                    $errors->add('min', "The minimum value for swap #{$swap_number} was not valid.");
                }
            } else {
                $errors->add('min', "Please specify a valid minimum value for swap #{$swap_number}");
            }


            // make sure assets aren't the same
            if ($assets_are_valid AND $in_value == $out_value) {
                $errors->add('rate', "The assets to receive and send for swap #{$swap_number} should not be the same.");
            }
        } else {
            $errors->add('swaps', "The values specified for swap #{$swap_number} were not valid.");
        }
    }

    ////////////////////////////////////////////////////////////////////////
    // Index

    public function buildIndexEntries(SwapConfig $swap_config) {
        return [
            [
                'in'   => $swap_config['in'],
                'out'  => $swap_config['out'],
                // 'rate' => $swap_config['rate'],
                'cost' => $swap_config['rate'] > 0 ? (1 / $swap_config['rate']) : 0,
            ]
        ];
    }

    public function buildSwapDetailsForAPI(SwapConfig $swap_config, $in=null) {
        return [
            'in'   => $swap_config['in'],
            'out'  => $swap_config['out'],
            'rate' => $swap_config['rate'],
            'cost' => $swap_config['rate'] > 0 ? (1 / $swap_config['rate']) : 0,
            'min'  => $swap_config['min'],
        ];
    }


}
