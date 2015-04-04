<?php

namespace Swapbot\Models\Formatting;

use Swapbot\Models\Data\SwapConfig;

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

}
