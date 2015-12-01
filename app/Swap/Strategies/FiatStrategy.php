<?php

namespace Swapbot\Swap\Strategies;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\RefundConfig;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Swap\Contracts\Strategy;
use Swapbot\Swap\Rules\SwapRuleHandler;
use Swapbot\Swap\Strategies\StrategyHelpers;
use Swapbot\Util\PricedTokens\PricedTokensHelper;
use Swapbot\Util\Validator\ValidatorHelper;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\QuotebotClient\Client as QuotebotClient;

class FiatStrategy implements Strategy {


    function __construct(QuotebotClient $quotebot_client, SwapRuleHandler $swap_rule_handler, PricedTokensHelper $priced_tokens_helper) {
        $this->quotebot_client      = $quotebot_client;
        $this->swap_rule_handler    = $swap_rule_handler;
        $this->priced_tokens_helper = $priced_tokens_helper;
    }

    public function shouldRefundTransaction(SwapConfig $swap_config, $quantity_in, $swap_rules=[]) {
        $value_data = $this->buildQuantityConversionData($swap_config, $quantity_in, $swap_rules);

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

    public function calculateInitialReceiptValues(SwapConfig $swap_config, $quantity_in, $swap_rules=[]) {
        $value_data = $this->buildQuantityConversionData($swap_config, $quantity_in, $swap_rules);
        $asset_out = $swap_config['out'];

        return [
            'quantityIn'          => $quantity_in,
            'assetIn'             => $swap_config['in'],

            'quantityOut'         => $value_data['quantityOut'],
            'assetOut'            => $swap_config['out'],

            'changeOut'           => $value_data['changeOut'],

            'conversionRate'      => $value_data['conversionRate'],

            'originalQuantityOut' => $value_data['originalQuantityOut'],
        ];
    }

    protected function buildQuantityConversionData(SwapConfig $swap_config, $quantity_in, $swap_rules) {

        $cost = $swap_config['cost'];
        $conversion_rate = $this->getFiatConversionRate($swap_config['in'], $swap_config['fiat'], $swap_config['source']);

        $raw_quantity_out = $quantity_in * $conversion_rate / $cost;
        $quantity_out = round($raw_quantity_out, 8);

        // execute the rule engine
        $original_quantity_out = null;
        $adjusted_quantity_in = $quantity_in;
        $modified_quantity_out = $this->swap_rule_handler->modifyInitialQuantityOut($raw_quantity_out, $swap_rules, $quantity_in, $swap_config);
        if ($modified_quantity_out !== null) {
            $original_quantity_out = $quantity_out;
            $quantity_out = $modified_quantity_out;
        }


        $change_quantity_out = 0;
        if (!$swap_config['divisible']) {
            // round down and calculate change
            $unrounded_quantity_out = $quantity_out;
            $quantity_out = floor($quantity_out);
            if ($unrounded_quantity_out > $quantity_out) {
                $needed_quantity_in = round($quantity_out * $cost / $conversion_rate, 8);
                $change_quantity_out = round($quantity_in - $needed_quantity_in, 8);
            }
        }

        $conversion_data = [
            'quantityOut'         => $quantity_out,
            'changeOut'           => $change_quantity_out,
            'conversionRate'      => $conversion_rate,
            'originalQuantityOut' => $original_quantity_out,
        ];

        return $conversion_data;

    }

    public function unSerializeDataToSwap($data, SwapConfig $swap_config) {
        // strategy is already set

        $swap_config['in']            = isset($data['in'])            ? $data['in']            : null;
        $swap_config['out']           = isset($data['out'])           ? $data['out']           : null;
        $swap_config['cost']          = isset($data['cost'])          ? $data['cost']          : null;

        $swap_config['min_out']       = isset($data['min_out'])       ? $data['min_out']       : 0;
        $swap_config['divisible']     = isset($data['divisible'])     ? !!$data['divisible']   : false;

        $swap_config['type']          = isset($data['type'])          ? $data['type']          : 'buy';
        $swap_config['fiat']          = isset($data['fiat'])          ? $data['fiat']          : 'USD';
        $swap_config['source']        = isset($data['source'])        ? $data['source']        : 'bitcoinAverage';
 
        $swap_config['direction']     = isset($data['direction'])     ? $data['direction']     : SwapConfig::DIRECTION_SELL;

        $swap_config['swap_rule_ids'] = isset($data['swap_rule_ids']) ? $data['swap_rule_ids'] : null;
   }

    public function serializeSwap(SwapConfig $swap_config) {
        return [
            'strategy'      => $swap_config['strategy'],
            'direction'     => ($swap_config['direction'] == SwapConfig::DIRECTION_BUY) ? SwapConfig::DIRECTION_BUY : SwapConfig::DIRECTION_SELL,
            'in'            => $swap_config['in'],
            'out'           => $swap_config['out'],
            'cost'          => $swap_config['cost'],
            'divisible'     => $swap_config['divisible'],
            'min_out'       => $swap_config['min_out'],

            'type'          => $swap_config['type'],
            'fiat'          => $swap_config['fiat'],
            'source'        => $swap_config['source'],

            'swap_rule_ids' => $swap_config['swap_rule_ids'],
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

            if ($in_value != 'BTC' AND !$this->priced_tokens_helper->isPriceableToken($in_value)) {
                $errors->add('type', "This token type is not supported");
            }

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
                if (!ValidatorHelper::isValidQuantityOrZero($min_out_value)) {
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

    public function validateSwapRuleConfig($swap_rule, MessageBag $errors) {
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
        $btc_conversion_rate = $this->getBTCConversionRate($fiat, $source);
        if ($asset == 'BTC') { return $btc_conversion_rate; }

        $asset_to_btc_rate = $this->getLowestAssetToBTCRate($asset);
        if (!$asset_to_btc_rate) {
            throw new Exception("Unable to convert asset $asset to fiat", 1);
        }

        return $asset_to_btc_rate * $btc_conversion_rate;
    }

    protected function getBTCConversionRate($fiat, $source) {
        try {
            $quote_entry = $this->quotebot_client->loadQuote($source, [$fiat, 'BTC']);
        } catch (Exception $e) {
            EventLog::logError('loadquote.failed', $e);

            // fallback to last cache
            $quote_entry = $this->quotebot_client->getQuote($source, [$fiat, 'BTC']);       
        }

        return $quote_entry['last'];
    }

    protected function getLowestAssetToBTCRate($asset, $asset_quote_source='poloniex') {
        try {
            $quote_entry = $this->quotebot_client->loadQuote($asset_quote_source, ['BTC', $asset]);
        } catch (Exception $e) {
            EventLog::logError('loadquote.failed', $e);

            // fallback to last cache
            $quote_entry = $this->quotebot_client->getQuote($asset_quote_source, ['BTC', $asset]);       
        }

        $value = min($quote_entry['last'], $quote_entry['lastAvg']);
        if ($quote_entry['inSatoshis']) {
            $value = CurrencyUtil::satoshisToValue($value);
        }

        return $value;
    }

}
