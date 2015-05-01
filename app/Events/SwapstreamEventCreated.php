<?php namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Bot;
use Swapbot\Models\Swap;

class SwapstreamEventCreated extends Event {

    var $swap;
    var $bot;
    var $event;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Swap $swap, Bot $bot, $event)
    {
        $this->bot   = $bot;
        $this->swap  = $swap;
        $this->event = $event;
    }

}
