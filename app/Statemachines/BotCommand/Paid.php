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
* Paid
*/
class Paid extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        // update the bot state in the database
        $this->updateBotState($bot, BotState::ACTIVE);

    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Payment Received';
    }



}
