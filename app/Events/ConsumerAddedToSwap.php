<?php

namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Consumer;
use Swapbot\Models\Swap;

class ConsumerAddedToSwap extends Event {

    var $consumer;
    var $swap;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Consumer $consumer, Swap $swap)
    {
        $this->consumer = $consumer;
        $this->swap     = $swap;
    }

}
