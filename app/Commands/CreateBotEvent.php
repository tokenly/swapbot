<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;

class CreateBotEvent extends Command {

    var $bot;
    var $level;
    var $event;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $level, $event)
    {
        $this->bot   = $bot;
        $this->level = $level;
        $this->event = $event;
    }


}
