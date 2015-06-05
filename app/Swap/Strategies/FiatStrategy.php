<?php

namespace Swapbot\Swap\Strategies;

use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Swap\Contracts\Strategy;
use Swapbot\Swap\Strategies\StrategyHelpers;
use Tokenly\QuotebotClient\Client as QuotebotClient;

class FiatStrategy implements Strategy {

    function __construct(QuotebotClient $quotebot_client) {
        $this->quotebot_client = $quotebot_client;
    }

    public function shouldRefundTransaction(SwapConfig $swap, $in_quantity) {
        // // if there is a minimum and the input is below this minimum
        // //   then it should be refunded
        // if ($in_quantity < $swap['min']) {
        //     return true;
        // }

        return false;
    }

    public function buildSwapOutputQuantityAndAsset($swap, $in_quantity) {
        $conversion_rate = $this->getFiatConversionRate($swap['in'], $swap['fiat'], $swap['source']);
        $quantity_out = $in_quantity * $conversion_rate / $swap['cost'];

        $asset_out = $swap['out'];
        return [$quantity_out, $asset_out];
    }

    public function unSerializeDataToSwap($data, SwapConfig $swap) {
        // strategy is already set

        $swap['in']        = isset($data['in'])        ? $data['in']        : null;
        $swap['out']       = isset($data['out'])       ? $data['out']       : null;
        $swap['cost']      = isset($data['cost'])      ? $data['cost']      : null;

        $swap['type']      = isset($data['type'])      ? $data['type']      : 'buy';
        $swap['min_out']   = isset($data['min_out'])   ? $data['min_out']   : 0;
        $swap['divisible'] = isset($data['divisible']) ? $data['divisible'] : false;

        $swap['fiat']      = isset($data['fiat'])      ? $data['fiat']      : 'USD';
        $swap['source']    = isset($data['source'])    ? $data['source']    : 'bitcoinAverage';
    }

    public function serializeSwap(SwapConfig $swap) {
        return [
            'strategy'  => $swap['strategy'],
            'in'        => $swap['in'],
            'out'       => $swap['out'],
            'cost'      => $swap['cost'],
            'type'      => $swap['type'],
            'divisible' => $swap['divisible'],
            'min_out'   => $swap['min_out'],

            'fiat'      => $swap['fiat'],
            'source'    => $swap['source'],
        ];
    }

    public function validateSwap($swap_number, $swap, MessageBag $errors) {
        // unimplemented
        return;

        $in_value   = isset($swap['in'])   ? $swap['in']   : null;
        $out_value  = isset($swap['out'])  ? $swap['out']  : null;
        $rate_value = isset($swap['cost']) ? $swap['cost'] : null;
        $min_value  = isset($swap['min'])  ? $swap['min']  : null;

        $exists = (strlen($in_value) OR strlen($out_value) OR strlen($rate_value));
        if ($exists) {
            $assets_are_valid = true;

            // in and out assets
            if (!StrategyHelpers::validateAssetName($in_value, 'receive', $swap_number, 'in', $errors)) { $assets_are_valid = false; }
            if (!StrategyHelpers::validateAssetName($out_value, 'send', $swap_number, 'out', $errors)) { $assets_are_valid = false; }

            // rate
            if (strlen($rate_value)) {
                if (!StrategyHelpers::isValidRate($rate_value)) {
                    $errors->add('cost', "The rate for swap #{$swap_number} was not valid.");
                }
            } else {
                $errors->add('cost', "Please specify a valid rate for swap #{$swap_number}");
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
                $errors->add('cost', "The assets to receive and send for swap #{$swap_number} should not be the same.");
            }
        } else {
            $errors->add('swaps', "The values specified for swap #{$swap_number} were not valid.");
        }
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Conversion

    protected function getFiatConversionRate($asset, $fiat, $source) {
        $quote_entry = $this->quotebot_client->getQuote($source, [$fiat, $asset]);

        return $quote_entry['last'];
    }
    
    

}
