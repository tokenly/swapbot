<?php

namespace Swapbot\Swap\Contracts;

use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\SwapConfig;

interface Strategy {

    public function caculateInitialReceiptValues(SwapConfig $swap_config, $quantity_in);

    public function shouldRefundTransaction(SwapConfig $swap_config, $quantity_in);
    public function buildRefundReason(SwapConfig $swap_config, $quantity_in);

    public function unSerializeDataToSwap($data, SwapConfig $swap_config);
    public function serializeSwap(SwapConfig $swap);

    public function validateSwap($swap_number, $swap, MessageBag $errors);

    public function buildIndexEntries(SwapConfig $swap_config);
    public function buildSwapDetailsForAPI(SwapConfig $swap_config, $in=null);

}
