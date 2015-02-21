<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Statemachines\BotCommand\BotCommand;


/*
* Fueled
*/
class Fueled extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        \Illuminate\Support\Facades\Log::debug('Fueled');

        // update the bot state in the database
        $this->updateBotState($bot, BotState::ACTIVE);

        // reconcile the state again
        $this->dispatch(new ReconcileBotState($bot));
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Fuel Received';
    }



}
