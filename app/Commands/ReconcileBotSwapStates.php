<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;

class ReconcileBotSwapStates extends Command {


    var $bot;
    var $block_height;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $block_height)
    {
        $this->bot          = $bot;
        $this->block_height = $block_height;
    }

}
