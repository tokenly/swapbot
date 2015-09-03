<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotFinishedShuttingDown;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Statemachines\BotCommand\BotCommand;


/*
* CompleteShutdown
*/
class CompleteShutdown extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        // update the bot state in the database
        $this->updateBotState($bot, BotState::SHUTDOWN);

        // fire an event
        Event::fire(new BotFinishedShuttingDown($bot));

    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Complete Shutdown';
    }



}
