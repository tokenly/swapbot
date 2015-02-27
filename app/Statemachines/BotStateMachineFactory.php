<?php

namespace Swapbot\Statemachines;

use Exception;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStateEvent;
use Swapbot\Statemachines\BotCommand\CreationFeePaid;
use Swapbot\Statemachines\BotCommand\Fueled;
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
        ];

    }

    // add transitions
    public function addTransitionsToStates($states) {
        // BotState::BRAND_NEW => BotState::LOW_FUEL
        $states[BotState::BRAND_NEW]->addTransition(new Transition($states[BotState::LOW_FUEL], BotStateEvent::CREATION_FEE_PAID));
        $states[BotState::BRAND_NEW]->getEvent(BotStateEvent::CREATION_FEE_PAID)->attach(new CreationFeePaid());

        // BotState::LOW_FUEL => BotState::LOW_FUEL
        $states[BotState::LOW_FUEL]->addTransition(new Transition($states[BotState::LOW_FUEL], BotStateEvent::CREATION_FEE_PAID));

        // BotState::LOW_FUEL => BotState::ACTIVE
        $states[BotState::LOW_FUEL]->addTransition(new Transition($states[BotState::ACTIVE], BotStateEvent::FUELED));
        $states[BotState::LOW_FUEL]->getEvent(BotStateEvent::FUELED)->attach(new Fueled());

        return $states;
    }


}
