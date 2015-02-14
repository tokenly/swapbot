<?php

namespace Swapbot\Swap\Contracts;

use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\SwapConfig;

interface Strategy {

    public function buildSwapOutputQuantityAndAsset($swap, $xchain_notification);

    public function unSerializeDataToSwap($data, SwapConfig $swap);
    public function serializeSwap(SwapConfig $swap);

    public function validateSwap($swap_number, $swap, MessageBag $errors);

}
