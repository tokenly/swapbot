<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Swap;

class DeleteSwap extends Command {

    var $swap;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Swap $swap)
    {
        $this->swap = $swap;
    }

}
