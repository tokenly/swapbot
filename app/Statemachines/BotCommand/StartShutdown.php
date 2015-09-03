<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotBeganShuttingDown;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Statemachines\BotCommand\BotCommand;


/*
* StartShutdown
*/
class StartShutdown extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        // update the bot state in the database
        $this->updateBotState($bot, BotState::SHUTTING_DOWN);

        // fire an event
        Event::fire(new BotBeganShuttingDown($bot));

    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Start Shutdown';
    }



}
