<?php

namespace Swapbot\Statemachines\BotPaymentCommand;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Statemachines\BotPaymentCommand\BotPaymentCommand;


/*
* EnteredOK
*/
class EnteredOK extends BotPaymentCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        $this->updateBotPaymentState($bot, BotPaymentState::OK);
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Payment state became OK';
    }



}
