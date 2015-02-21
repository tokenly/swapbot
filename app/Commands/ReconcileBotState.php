<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;

class ReconcileBotState extends Command {


    var $bot;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
    }

}
