<?php namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Bot;

class BotstreamEventCreated extends Event {

    var $bot;
    var $event;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $event)
    {
        $this->bot   = $bot;
        $this->event = $event;
    }

}
