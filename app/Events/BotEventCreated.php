<?php namespace Swapbot\Events;

use Illuminate\Queue\SerializesModels;
use Swapbot\Events\Event;
use Swapbot\Models\Bot;

class BotEventCreated extends Event {

    var $bot;
    var $event;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $event)
    {
        $this->bot = $bot;
        $this->event = $event;
    }

}
