<?php

namespace Swapbot\Swap\Strategies;

use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\RefundConfig;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Swap\Contracts\Strategy;
use Swapbot\Swap\Strategies\StrategyHelpers;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\QuotebotClient\Client as QuotebotClient;
use Exception;

class FiatStrategy implements Strategy {

    function __construct(QuotebotClient $quotebot_client) {
        $this->quotebot_client = $quotebot_client;
    }

    public function shouldRefundTransaction(SwapConfig $swap_config, $quantity_in) {
        $value_data = $this->buildQuantityConversionData($swap_config, $quantity_in);

        // if this input would not purchase any outAsset or is below the minimum
        //   then it should be refunded
        if ($value_data['quantityOut'] <= 0 OR $value_data['quantityOut'] < $swap_config['min_out']) {
            return true;
        }

        return false;
    }

    public function buildRefundReason(SwapConfig $swap_config, $quantity_in) {
        return RefundConfig::REASON_BELOW_MINIMUM;
    }

    public function caculateInitialReceiptValues(SwapConfig $swap_config, $quantity_in) {
        $value_data = $this->buildQuantityConversionData($swap_config, $quantity_in);
        $asset_out = $swap_config['out'];

        return [
            'quantityIn'     => $quantity_in,
            'assetIn'        => $swap_config['in'],

            'quantityOut'    => $value_data['quantityOut'],
            'assetOut'       => $swap_config['out'],

            'changeOut'      => $value_data['changeOut'],

            'conversionRate' => $value_data['conversionRate'],
        ];
    }

    protected function buildQuantityConversionData(SwapConfig $swap_config, $quantity_in) {
        $cost = $swap_config['cost'];
        $conversion_rate = $this->getFiatConversionRate($swap_config['in'], $swap_config['fiat'], $swap_config['source']);
        $quantity_out = $quantity_in * $conversion_rate / $cost;

        $change_quantity_out = 0;
        if (!$swap_config['divisible']) {
            // round down and calculate change
            $raw_quantity_out = $quantity_out;
            $quantity_out = floor($quantity_out);
            if ($raw_quantity_out > $quantity_out) {
                $needed_quantity_in = $quantity_out * $cost / $conversion_rate;
                $change_quantity_out = $quantity_in - $needed_quantity_in;
            }
        }

        return [
            'quantityOut'    => $quantity_out,
            'changeOut'      => $change_quantity_out,
            'conversionRate' => $conversion_rate,
        ];

    }

    public function unSerializeDataToSwap($data, SwapConfig $swap_config) {
        // strategy is already set

        $swap_config['in']        = isset($data['in'])        ? $data['in']        : null;
        $swap_config['out']       = isset($data['out'])       ? $data['out']       : null;
        $swap_config['cost']      = isset($data['cost'])      ? $data['cost']      : null;

        $swap_config['min_out']   = isset($data['min_out'])   ? $data['min_out']   : 0;
        $swap_config['divisible'] = isset($data['divisible']) ? !!$data['divisible'] : false;

        $swap_config['type']      = isset($data['type'])      ? $data['type']      : 'buy';
        $swap_config['fiat']      = isset($data['fiat'])      ? $data['fiat']      : 'USD';
        $swap_config['source']    = isset($data['source'])    ? $data['source']    : 'bitcoinAverage';
 
        $swap_config['direction'] = isset($data['direction']) ? $data['direction'] : SwapConfig::DIRECTION_SELL;
   }

    public function serializeSwap(SwapConfig $swap_config) {
        return [
            'strategy'  => $swap_config['strategy'],
            'direction' => ($swap_config['direction'] == SwapConfig::DIRECTION_BUY) ? SwapConfig::DIRECTION_BUY : SwapConfig::DIRECTION_SELL,
            'in'        => $swap_config['in'],
            'out'       => $swap_config['out'],
            'cost'      => $swap_config['cost'],
            'divisible' => $swap_config['divisible'],
            'min_out'   => $swap_config['min_out'],

            'type'      => $swap_config['type'],
            'fiat'      => $swap_config['fiat'],
            'source'    => $swap_config['source'],
        ];
    }

    public function validateSwap($swap_config_number, $swap_config, MessageBag $errors) {
        $in_value   = isset($swap_config['in'])   ? $swap_config['in']   : null;
        $out_value  = isset($swap_config['out'])  ? $swap_config['out']  : null;
        $cost_value = isset($swap_config['cost']) ? $swap_config['cost'] : null;
        $min_out_value  = isset($swap_config['min_out'])  ? $swap_config['min_out']  : null;
        $direction_value = isset($swap_config['direction']) ? $swap_config['direction'] : SwapConfig::DIRECTION_SELL;

        $exists = (strlen($in_value) OR strlen($out_value) OR strlen($cost_value));
        if ($exists) {
            $assets_are_valid = true;

            // direction
            if ($direction_value != SwapConfig::DIRECTION_SELL AND $direction_value != SwapConfig::DIRECTION_BUY) {
                $errors->add('direction', "Please specify a valid direction for swap #{$swap_number}");
            }

            // in and out assets
            if (!StrategyHelpers::validateAssetName($in_value, 'receive', $swap_config_number, 'in', $errors)) { $assets_are_valid = false; }
            if (!StrategyHelpers::validateAssetName($out_value, 'send', $swap_config_number, 'out', $errors)) { $assets_are_valid = false; }
            if ($in_value !== 'BTC') { $errors->add('type', "Only BTC is supported"); }

            // cost
            if (strlen($cost_value)) {
                if (!StrategyHelpers::isValidCost($cost_value)) {
                    $errors->add('cost', "The cost for swap #{$swap_config_number} was not valid.");
                }
            } else {
                $errors->add('cost', "Please specify a valid cost for swap #{$swap_config_number}");
            }

            // min_out
            if (strlen($min_out_value)) {
                if (!StrategyHelpers::isValidQuantityOrZero($min_out_value)) {
                    $errors->add('min_out', "The minimum output value for swap #{$swap_config_number} was not valid.");
                }
            } else {
                $errors->add('min_out', "Please specify a valid minimum value for swap #{$swap_config_number}");
            }

            // 'type'      => $swap_config['type'],
            // 'fiat'      => $swap_config['fiat'],
            // 'source'    => $swap_config['source'],
            if (!isset($swap_config['type']) OR $swap_config['type'] !== 'buy') { $errors->add('type', "Only type of buy is supported"); }
            if (!isset($swap_config['fiat']) OR $swap_config['fiat'] !== 'USD') { $errors->add('type', "Only USD is supported"); }
            if (!isset($swap_config['source']) OR $swap_config['source'] !== 'bitcoinAverage') { $errors->add('type', "Only bitcoinAverage is supported"); }

            // make sure assets aren't the same
            if ($assets_are_valid AND $in_value == $out_value) {
                $errors->add('cost', "The assets to receive and send for swap #{$swap_config_number} should not be the same.");
            }
        } else {
            $errors->add('swaps', "The values specified for swap #{$swap_config_number} were not valid.");
        }
    }

    ////////////////////////////////////////////////////////////////////////
    // Index

    public function buildIndexEntries(SwapConfig $swap_config) {
        $conversion_rate = $this->getFiatConversionRate($swap_config['in'], $swap_config['fiat'], $swap_config['source']);

        return [
            [
                'in'   => $swap_config['fiat'],
                'out'  => $swap_config['out'],
                // 'rate' => (1 / $swap_config['cost']),
                'cost' => ($swap_config['cost']),
            ],
            [
                'in'   => $swap_config['in'],
                'out'  => $swap_config['out'],
                // 'rate' => ($conversion_rate / $swap_config['cost']),
                'cost' => ($swap_config['cost'] / $conversion_rate),
            ],
        ];
    }

    public function buildSwapDetailsForAPI(SwapConfig $swap_config, $in=null) {
        $conversion_rate = $this->getFiatConversionRate($swap_config['in'], $swap_config['fiat'], $swap_config['source']);

        if ($in AND $in == $swap_config['fiat']) {
            return [
                'in'        => $swap_config['fiat'],
                'out'       => $swap_config['out'],
                'rate'      => (1 / $swap_config['cost']),
                'cost'      => ($swap_config['cost']),

                'divisible' => $swap_config['divisible'],
                'min'       => $swap_config['min_out'],
                'fiat'      => $swap_config['fiat'],
            ];
        }

        return [
            'in'        => $swap_config['in'],
            'out'       => $swap_config['out'],
            'rate'      => ($conversion_rate / $swap_config['cost']),
            'cost'      => ($swap_config['cost'] / $conversion_rate),

            'divisible' => $swap_config['divisible'],
            'min'       => $swap_config['min_out'],
            'fiat'      => $swap_config['fiat'],
        ];
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Conversion

    protected function getFiatConversionRate($asset, $fiat, $source) {
        try {
            $quote_entry = $this->quotebot_client->loadQuote($source, [$fiat, $asset]);
        } catch (Exception $e) {
            EventLog::logError('loadquote.failed', $e);

            // fallback to last cache
            $quote_entry = $this->quotebot_client->getQuote($source, [$fiat, $asset]);       
        }

        return $quote_entry['last'];
    }

    

}
