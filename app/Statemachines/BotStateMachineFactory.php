<?php

namespace Swapbot\Statemachines;

use Exception;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStateEvent;
use Swapbot\Statemachines\BotCommand\CompleteShutdown;
use Swapbot\Statemachines\BotCommand\ExhaustedBotMonthlyFeePaid;
use Swapbot\Statemachines\BotCommand\FirstMonthlyFeePaid;
use Swapbot\Statemachines\BotCommand\FuelExhausted;
use Swapbot\Statemachines\BotCommand\Fueled;
use Swapbot\Statemachines\BotCommand\LeaseExpired;
use Swapbot\Statemachines\BotCommand\MonthlyFeePaid;
use Swapbot\Statemachines\BotCommand\PaymentExhausted;
use Swapbot\Statemachines\BotCommand\Revived;
use Swapbot\Statemachines\BotCommand\StartShutdown;
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
            BotState::BRAND_NEW     => new BotState(BotState::BRAND_NEW),
            BotState::PAYING        => new BotState(BotState::PAYING),
            BotState::UNPAID        => new BotState(BotState::UNPAID),
            BotState::LOW_FUEL      => new BotState(BotState::LOW_FUEL),
            BotState::ACTIVE        => new BotState(BotState::ACTIVE),
            BotState::INACTIVE      => new BotState(BotState::INACTIVE),
            BotState::SHUTTING_DOWN => new BotState(BotState::SHUTTING_DOWN),
            BotState::SHUTDOWN      => new BotState(BotState::SHUTDOWN),
        ];

    }

    // add transitions
    public function addTransitionsToStates($states) {
        // BotState::BRAND_NEW => BotState::LOW_FUEL
        $this->addTransitionToStates($states, BotState::BRAND_NEW,     BotState::LOW_FUEL,      BotStateEvent::FIRST_MONTHLY_FEE_PAID, new FirstMonthlyFeePaid());

        // BotState::LOW_FUEL => BotState::ACTIVE
        $this->addTransitionToStates($states, BotState::LOW_FUEL,      BotState::ACTIVE,        BotStateEvent::FUELED,                 new Fueled());

        // BotState::LOW_FUEL => BotState::PAYING
        $this->addTransitionToStates($states, BotState::LOW_FUEL,      BotState::PAYING,        BotStateEvent::LEASE_EXPIRED,          new LeaseExpired());

        // BotState::ACTIVE => BotState::LOW_FUEL
        $this->addTransitionToStates($states, BotState::ACTIVE,        BotState::LOW_FUEL,      BotStateEvent::FUEL_EXHAUSTED,         new FuelExhausted());

        // BotState::ACTIVE => BotState::PAYING
        $this->addTransitionToStates($states, BotState::ACTIVE,        BotState::PAYING,        BotStateEvent::LEASE_EXPIRED,          new LeaseExpired());

        // BotState::PAYING => BotState::ACTIVE
        $this->addTransitionToStates($states, BotState::PAYING,        BotState::ACTIVE,        BotStateEvent::MONTHLY_FEE_PAID,       new MonthlyFeePaid());

        // BotState::PAYING => BotState::UNPAID
        $this->addTransitionToStates($states, BotState::PAYING,        BotState::UNPAID,        BotStateEvent::PAYMENT_EXHAUSTED,      new PaymentExhausted());


        // BotState::UNPAID => BotState::ACTIVE
        $this->addTransitionToStates($states, BotState::UNPAID,        BotState::ACTIVE,        BotStateEvent::MONTHLY_FEE_PAID,       new ExhaustedBotMonthlyFeePaid());



        // BotState::ACTIVE => BotState::SHUTTING_DOWN
        $this->addTransitionToStates($states, BotState::ACTIVE,        BotState::SHUTTING_DOWN, BotStateEvent::START_SHUTDOWN,         new StartShutdown());

        // BotState::UNPAID => BotState::SHUTTING_DOWN
        $this->addTransitionToStates($states, BotState::UNPAID,        BotState::SHUTTING_DOWN, BotStateEvent::START_SHUTDOWN,         new StartShutdown());

        // BotState::LOW_FUEL => BotState::SHUTTING_DOWN
        $this->addTransitionToStates($states, BotState::LOW_FUEL,        BotState::SHUTTING_DOWN, BotStateEvent::START_SHUTDOWN,       new StartShutdown());


        // BotState::SHUTTING_DOWN => BotState::SHUTDOWN
        $this->addTransitionToStates($states, BotState::SHUTTING_DOWN, BotState::SHUTDOWN,      BotStateEvent::COMPLETE_SHUTDOWN,      new CompleteShutdown());


        // BotState::SHUTDOWN => BotState::ACTIVE
        $this->addTransitionToStates($states, BotState::SHUTDOWN,      BotState::ACTIVE,        BotStateEvent::REVIVED,                new Revived());



        return $states;
    }


}
