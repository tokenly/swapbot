<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Statemachines\BotCommand\BotCommand;


/*
* PaymentExhausted
*/
class PaymentExhausted extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        // update the bot state in the database
        $this->updateBotState($bot, BotState::UNPAID);

    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Payment Exhausted';
    }



}
