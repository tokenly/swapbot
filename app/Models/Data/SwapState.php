<?php

namespace Swapbot\Models\Data;

use Metabor\Statemachine\State;

class SwapState extends State {

    const BRAND_NEW       = 'brandnew';
    const OUT_OF_STOCK    = 'outofstock';
    const OUT_OF_FUEL     = 'outoffuel';
    const READY           = 'ready';
    const CONFIRMING      = 'confirming';
    const SENT            = 'sent';
    const REFUNDED        = 'refunded';
    const COMPLETE        = 'complete';
    const ERROR           = 'error';
    const PERMANENT_ERROR = 'permanenterror';
    const INVALIDATED     = 'invalidated';

    // states that are not final
    public static function allPendingStates() {
        return [self::BRAND_NEW, self::OUT_OF_STOCK, self::OUT_OF_FUEL, self::READY, self::CONFIRMING, self::ERROR];
    }

    public static function friendlyLabel($state) {
        switch ($state) {
            case 'brandnew':       return 'Brand New';
            case 'outofstock':     return 'Out of Stock';
            case 'outoffuel':      return 'Out of Fuel';
            case 'ready':          return 'Ready';
            case 'confirming':     return 'Confirming';
            case 'sent':           return 'Sent';
            case 'refunded':       return 'Refunded';
            case 'complete':       return 'Complete';
            case 'error':          return 'Error';
            case 'permanenterror': return 'Permanent Error';
            case 'invalidated':    return 'Invalidated';

            default:               return 'Unknown';
        }
    }

}
