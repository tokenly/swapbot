<?php

namespace Swapbot\Models\Data;


class SwapStateEvent {

    const STOCK_CHECKED            = 'stockChecked';
    const STOCK_DEPLETED           = 'stockDepleted';
    const FUEL_CHECKED             = 'fuelChecked';
    const FUEL_DEPLETED            = 'fuelDepleted';
    const SWAP_ERRORED             = 'swapErrored';
    const SWAP_PERMANENTLY_ERRORED = 'swapPermanentlyErrored';
    const SWAP_SENT                = 'swapSent';
    const SWAP_REFUND              = 'swapRefund';
    const SWAP_COMPLETED           = 'swapCompleted';
    const CONFIRMING               = 'confirming';
    const CONFIRMED                = 'confirmed';

    const SWAP_WAS_INVALIDATED     = 'invalidated';

    const SWAP_RETRY               = 'swapRetry';
    const SWAP_RESET               = 'swapReset';


}
