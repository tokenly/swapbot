<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotFuelWasExhausted;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Statemachines\BotCommand\BotCommand;


/*
* FuelExhausted
*/
class FuelExhausted extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        // update the bot state in the database
        $this->updateBotState($bot, BotState::LOW_FUEL);

        // fire an event
        Event::fire(new BotFuelWasExhausted($bot));

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
