<?php

namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Swap;

class SwapWasConfirmed extends Event {

    var $swap;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Swap $swap)
    {
        $this->swap = $swap;
    }
}
