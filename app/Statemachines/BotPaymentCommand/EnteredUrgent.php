<?php

namespace Swapbot\Statemachines\BotPaymentCommand;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotPaymentStateBecameUrgent;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Statemachines\BotPaymentCommand\BotPaymentCommand;


/*
* EnteredUrgent
*/
class EnteredUrgent extends BotPaymentCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        $this->updateBotPaymentState($bot, BotPaymentState::URGENT);

        // fire an event
        Event::fire(new BotPaymentStateBecameUrgent($bot));
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Payment state became URGENT';
    }



}
