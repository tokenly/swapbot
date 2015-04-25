<?php

namespace Swapbot\Models\Formatting;

use Carbon\Carbon;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapState;

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

    public function stateIcon($state) {
        switch ($state) {
            case 'complete': return 'confirmed';
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

}
