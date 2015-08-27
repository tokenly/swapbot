<?php

namespace Swapbot\Models\Formatting;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Swap;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\QuotebotClient\Client;

class FormattingHelper {

    public function __construct(Client $quotebot_client) {
        $this->quotebot_client = $quotebot_client;
    }

    public function buildSwapDescription(SwapConfig $swap) {
        // Sends 1 BTC for 1,000,000 LTBCOIN or 1,000,000 NOTLTBCOIN.
        switch ($swap['strategy']) {
            case 'rate':
                return $this->buildSwapDescriptionForRateStrategy($swap);
                break;

            case 'fixed':
                return $this->buildSwapDescriptionForFixedStrategy($swap);
                break;
        }

        return 'unknown swap';
    }


    public function buildSwapDescriptionForRateStrategy(SwapConfig $swap) {
        $out_amount = 1 / $swap['rate'];
        $in_amount = 1;
        return "{$out_amount} {$swap['out']} for {$in_amount} {$swap['in']}";
    }

    public function buildSwapDescriptionForFixedStrategy(SwapConfig $swap) {
        return "{$swap['out_qty']} {$swap['out']} for {$in_qty} {$swap['in']}";
    }

    public function formatState($state) {
        return SwapState::friendlyLabel($state);
    }

    public function buildStateIcon(Swap $swap) {
        $state = $swap['state'];
        switch ($state) {
            case 'complete':
                if ($swap['receipt']['type'] == 'refund') {
                    return 'failed';
                }
                return 'confirmed';
            case 'error': return 'failed';
            default: return 'pending';
        }
    }

    public function swapStateDotColor(Swap $swap) {
        $state = $swap['state'];
        switch ($state) {
            case 'brandnew':
            case 'ready':
            case 'confirming':
            case 'sent':
            case 'complete':
                if (isset($swap['receipt']['type']) && $swap['receipt']['type'] == 'refund') {
                    return 'red';
                }
                return 'green';
            default:
                return 'red';
        }
    }

    public function formatDate($date) {
        if (!($date instanceof Carbon)) { $date = Carbon::parse($date); }

        if (($date instanceof Carbon) AND $date->getTimestamp() > 0) {
            return $date->format('D, M j, Y g:i A T');
        }
        return $date;
    }

    public function formatBlockchainHref($txid, $asset) {
        if (!strlen($txid)) { return '#unknown'; }
        if ($asset == 'BTC') {
            return 'https://chain.so/tx/BTC/'.$txid;
        } else {
            return 'https://counterpartychain.io/transaction/'.$txid;
        }
    }

    public function formatAddressHref($address) {
        if (!strlen($address)) { return '#unknown'; }
        return 'https://counterpartychain.io/address/'.$address;
    }

    public function fiatSuffix($swap_config_strategy, $in_qty, $asset, $conversion_rate=null) {
        if ($asset != 'BTC') { return ''; }
        if ($swap_config_strategy == 'fiat') {
            if ($conversion_rate === null) {
                $quote_entry = $this->quotebot_client->getQuote('bitcoinAverage', ['USD', 'BTC']);
                $conversion_rate = $quote_entry['last'];
            }

            $quantity = $in_qty * $conversion_rate;
            return ' ($'.money_format('%i', $quantity).')';
        }
        return '';
    }

    // 123 satoshis (0.00000123)
    public function formatCurrency($value, $places=null) {
        if (($places === null OR $places == 8) AND $value < 0.0001) {
            $text = 
                CurrencyUtil::valueToSatoshis($value).' satoshis'.
                ' ('.CurrencyUtil::valueToFormattedString($value, $places).')';
            return $text;
        }

        return CurrencyUtil::valueToFormattedString($value, $places);
    }

    public function implodeWithConjunction($list, $article='and') {
        if (count($list) == 0) {
            return '';
        } else if (count($list) <= 1) {
            return $list[0];
        }

        $out = implode(', ', array_slice($list, 0, -1));
        return $out.' '.$article.' '.$list[count($list)-1];
    }

}
