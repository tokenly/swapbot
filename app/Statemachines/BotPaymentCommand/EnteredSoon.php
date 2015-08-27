<?php

namespace Swapbot\Statemachines\BotPaymentCommand;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotPaymentStateBecameSoon;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Statemachines\BotPaymentCommand\BotPaymentCommand;


/*
* EnteredSoon
*/
class EnteredSoon extends BotPaymentCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        $this->updateBotPaymentState($bot, BotPaymentState::SOON);

        // fire an event
        Event::fire(new BotPaymentStateBecameSoon($bot));
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Payment state became SOON';
    }



}
