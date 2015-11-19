<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Statemachines\BotCommand\BotCommand;


/*
* Revived
*/
class Revived extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        // update the bot state in the database
        $this->updateBotState($bot, BotState::ACTIVE);

        // fire an event
        // Event::fire(new BotRevived($bot));

    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Revived';
    }



}
