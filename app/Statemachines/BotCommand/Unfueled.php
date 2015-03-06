<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Statemachines\BotCommand\BotCommand;


/*
* Unfueled
*/
class Unfueled extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        // update the bot state in the database
        $this->updateBotState($bot, BotState::LOW_FUEL);

    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Fuel Exhausted';
    }



}
