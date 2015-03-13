<?php

namespace Swapbot\Models\Data;

use Metabor\Statemachine\State;

class SwapState extends State {

    const BRAND_NEW    = 'brandnew';
    const OUT_OF_STOCK = 'outofstock';
    const READY        = 'ready';
    const CONFIRMING   = 'confirming';
    const SENT         = 'sent';
    const COMPLETE     = 'complete';
    const ERROR        = 'error';

    // states that are not final
    public static function allPendingStates() {
        return [self::BRAND_NEW, self::OUT_OF_STOCK, self::READY, self::CONFIRMING, self::ERROR];
    }

}
