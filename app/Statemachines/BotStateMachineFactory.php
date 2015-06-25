<?php

namespace Swapbot\Statemachines;

use Exception;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStateEvent;
use Swapbot\Statemachines\BotCommand\FirstMonthlyFeePaid;
use Swapbot\Statemachines\BotCommand\FuelExhausted;
use Swapbot\Statemachines\BotCommand\Fueled;
use Swapbot\Statemachines\BotCommand\MonthlyFeePaid;
use Swapbot\Statemachines\BotCommand\LeaseExpired;
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
            BotState::UNPAID    => new BotState(BotState::UNPAID),
            BotState::LOW_FUEL  => new BotState(BotState::LOW_FUEL),
            BotState::ACTIVE    => new BotState(BotState::ACTIVE),
            BotState::INACTIVE  => new BotState(BotState::INACTIVE),
        ];

    }

    // add transitions
    public function addTransitionsToStates($states) {
        // BotState::BRAND_NEW => BotState::LOW_FUEL
        $this->addTransitionToStates($states, BotState::BRAND_NEW, BotState::LOW_FUEL, BotStateEvent::FIRST_MONTHLY_FEE_PAID, new FirstMonthlyFeePaid());

        // BotState::LOW_FUEL => BotState::ACTIVE
        $this->addTransitionToStates($states, BotState::LOW_FUEL,  BotState::ACTIVE,   BotStateEvent::FUELED,                 new Fueled());

        // BotState::LOW_FUEL => BotState::UNPAID
        $this->addTransitionToStates($states, BotState::LOW_FUEL,  BotState::UNPAID,   BotStateEvent::LEASE_EXPIRED,          new LeaseExpired());

        // BotState::ACTIVE => BotState::LOW_FUEL
        $this->addTransitionToStates($states, BotState::ACTIVE,    BotState::LOW_FUEL, BotStateEvent::FUEL_EXHAUSTED,         new FuelExhausted());

        // BotState::ACTIVE => BotState::UNPAID
        $this->addTransitionToStates($states, BotState::ACTIVE,    BotState::UNPAID,   BotStateEvent::LEASE_EXPIRED,          new LeaseExpired());

        // BotState::UNPAID => BotState::ACTIVE
        $this->addTransitionToStates($states, BotState::UNPAID,    BotState::ACTIVE,   BotStateEvent::MONTHLY_FEE_PAID,       new MonthlyFeePaid());

        return $states;
    }


}
