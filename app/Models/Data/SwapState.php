<?php

namespace Swapbot\Models\Data;

use Metabor\Statemachine\State;

class SwapState extends State {

    const BRAND_NEW    = 'brandnew';
    const OUT_OF_STOCK = 'outofstock';
    const READY        = 'ready';
    const SENT         = 'sent';
    const COMPLETE     = 'complete';

}
