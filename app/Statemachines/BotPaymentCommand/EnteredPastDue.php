<?php

namespace Swapbot\Statemachines\BotPaymentCommand;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotPaymentStateBecamePastDue;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Statemachines\BotPaymentCommand\BotPaymentCommand;


/*
* EnteredPastDue
*/
class EnteredPastDue extends BotPaymentCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        $this->updateBotPaymentState($bot, BotPaymentState::PAST_DUE);

        // fire an event
        Event::fire(new BotPaymentStateBecamePastDue($bot));
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Payment state became PAST_DUE';
    }



}
