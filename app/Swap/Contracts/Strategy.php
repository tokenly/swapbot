<?php

namespace Swapbot\Swap\Contracts;

use Illuminate\Support\MessageBag;
use Swapbot\Models\Data\SwapConfig;

interface Strategy {

    public function shouldRefundTransaction(SwapConfig $swap, $in_quantity);

    public function buildSwapOutputQuantityAndAsset($swap, $in_quantity);

    public function unSerializeDataToSwap($data, SwapConfig $swap);
    public function serializeSwap(SwapConfig $swap);



    public function validateSwap($swap_number, $swap, MessageBag $errors);


}
