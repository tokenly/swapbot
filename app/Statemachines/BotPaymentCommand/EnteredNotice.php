<?php

namespace Swapbot\Statemachines\BotPaymentCommand;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotPaymentStateBecameNotice;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Statemachines\BotPaymentCommand\BotPaymentCommand;


/*
* EnteredNotice
*/
class EnteredNotice extends BotPaymentCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        $this->updateBotPaymentState($bot, BotPaymentState::NOTICE);

        // fire an event
        Event::fire(new BotPaymentStateBecameNotice($bot));
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Payment state became NOTICE';
    }



}
