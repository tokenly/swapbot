<?php

namespace Swapbot\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Swapbot\Events\Event;
use Swapbot\Models\Swap;

class SwapWasInvalidated extends Event {

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
