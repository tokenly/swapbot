<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Swap;

class ReconcileSwapState extends Command {


    var $swap;
    var $block_height;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Swap $swap, $block_height)
    {
        $this->swap         = $swap;
        $this->block_height = $block_height;
    }

}
