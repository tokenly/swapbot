<?php

namespace Swapbot\Models\Data;


class SwapStateEvent {

    const STOCK_CHECKED  = 'stockChecked';
    const STOCK_DEPLETED = 'stockDepleted';
    const SWAP_ERRORED   = 'swapErrored';
    const SWAP_SENT      = 'swapSent';
    const SWAP_REFUND    = 'swapRefund';
    const SWAP_COMPLETED = 'swapCompleted';
    const CONFIRMING     = 'confirming';
    const CONFIRMED      = 'confirmed';

    const SWAP_RETRY     = 'swapRetry';


}
