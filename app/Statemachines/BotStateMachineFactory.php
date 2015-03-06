<?php

namespace Swapbot\Statemachines;

use Exception;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStateEvent;
use Swapbot\Statemachines\BotCommand\CreationFeePaid;
use Swapbot\Statemachines\BotCommand\FuelExhausted;
use Swapbot\Statemachines\BotCommand\Fueled;
use Swapbot\Statemachines\BotCommand\Paid;
use Swapbot\Statemachines\BotCommand\PaymentExhausted;
use Swapbot\Statemachines\StateMachineFactory;

/*
* BotStateMachineFactory
*/
class BotStateMachineFactory extends StateMachineFactory {

    public function __construct() {
    }

    public function buildStateMachineFromBot(Bot $bot) {
        return $this->buildStateMachineFromModel($bot);
    }


    public function buildStates() {
        // build states
        return [
            BotState::BRAND_NEW => new BotState(BotState::BRAND_NEW),
            BotState::LOW_FUEL  => new BotState(BotState::LOW_FUEL),
            BotState::ACTIVE    => new BotState(BotState::ACTIVE),
            BotState::INACTIVE  => new BotState(BotState::INACTIVE),
            BotState::UNPAID    => new BotState(BotState::UNPAID),
        ];

    }

    // add transitions
    public function addTransitionsToStates($states) {
        // BotState::BRAND_NEW => BotState::LOW_FUEL
        $this->addTransitionToStates($states, BotState::BRAND_NEW, BotState::LOW_FUEL, BotStateEvent::CREATION_FEE_PAID, new CreationFeePaid());

        // BotState::LOW_FUEL => BotState::LOW_FUEL
        $this->addTransitionToStates($states, BotState::LOW_FUEL, BotState::LOW_FUEL, BotStateEvent::CREATION_FEE_PAID, null);

        // BotState::LOW_FUEL => BotState::ACTIVE
        $this->addTransitionToStates($states, BotState::LOW_FUEL, BotState::ACTIVE, BotStateEvent::FUELED, new Fueled());

        // BotState::ACTIVE => BotState::LOW_FUEL
        $this->addTransitionToStates($states, BotState::ACTIVE, BotState::LOW_FUEL, BotStateEvent::FUEL_EXHAUSTED, new FuelExhausted());

        // BotState::ACTIVE => BotState::UNPAID
        $this->addTransitionToStates($states, BotState::ACTIVE, BotState::UNPAID, BotStateEvent::PAYMENT_EXHAUSTED, new PaymentExhausted());

        // BotState::UNPAID => BotState::ACTIVE
        $this->addTransitionToStates($states, BotState::UNPAID, BotState::ACTIVE, BotStateEvent::PAID, new Paid());

        return $states;
    }


}
