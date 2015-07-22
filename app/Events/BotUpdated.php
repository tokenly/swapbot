<?php

namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Bot;

class BotUpdated extends Event
{

    var $bot;
    var $update_type = 'update';

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
    }

}
