<?php

namespace Swapbot\Models\Formatting;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Swap;

class SwapFormatter {

    public function __construct() {
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

    public function stateDotColor($state) {
        switch ($state) {
            case 'brandnew':
            case 'ready':
            case 'confirming':
            case 'sent':
            case 'complete':
                if ($swap['receipt']['type'] == 'refund') {
                    return 'red';
                }
                return 'green';
            default:
                return 'red';
        }
    }

    public function formatDate($date) {
        if ($date instanceof Carbon) {
            return $date->format('D, M j, Y g:i A T');
        }
        return $date;
    }

    public function formatBlockchainHref($txid, $asset) {
        if (!strlen($txid)) { return '#unknown'; }
        if ($asset == 'BTC') {
            return 'https://blockchain.info/tx/'.$txid;
        } else {
            return 'https://counterpartychain.io/transaction/'.$txid;
        }
    }

    public function formatAddressHref($address) {
        if (!strlen($address)) { return '#unknown'; }
        return 'https://counterpartychain.io/address/'.$address;
    }

}
