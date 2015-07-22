<?php

namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Bot;

class BotDeleted extends Event
{

    var $bot;
    var $update_type = 'delete';

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
