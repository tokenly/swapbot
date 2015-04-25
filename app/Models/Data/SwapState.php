<?php

namespace Swapbot\Models\Data;

use Metabor\Statemachine\State;

class SwapState extends State {

    const BRAND_NEW    = 'brandnew';
    const OUT_OF_STOCK = 'outofstock';
    const READY        = 'ready';
    const CONFIRMING   = 'confirming';
    const SENT         = 'sent';
    const REFUNDED     = 'refunded';
    const COMPLETE     = 'complete';
    const ERROR        = 'error';

    // states that are not final
    public static function allPendingStates() {
        return [self::BRAND_NEW, self::OUT_OF_STOCK, self::READY, self::CONFIRMING, self::ERROR];
    }

    public static function friendlyLabel($state) {
        switch ($state) {
            case 'brandnew': return 'Brand New';
            case 'outofstock': return 'Out of Stock';
            case 'ready': return 'Ready';
            case 'confirming': return 'Confirming';
            case 'sent': return 'Sent';
            case 'refunded': return 'Refunded';
            case 'complete': return 'Complete';
            case 'error': return 'Error';
            default: return 'Unknown';
        }
    }

}
