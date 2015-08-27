<?php

namespace Swapbot\Statemachines;

use Exception;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Models\Data\BotPaymentStateEvent;
use Swapbot\Statemachines\BotPaymentCommand\EnteredNotice;
use Swapbot\Statemachines\BotPaymentCommand\EnteredOK;
use Swapbot\Statemachines\BotPaymentCommand\EnteredPastDue;
use Swapbot\Statemachines\BotPaymentCommand\EnteredSoon;
use Swapbot\Statemachines\BotPaymentCommand\EnteredUrgent;
use Swapbot\Statemachines\StateMachineFactory;

/*
* BotPaymentStateMachineFactory
*/
class BotPaymentStateMachineFactory extends StateMachineFactory {

    public function __construct() {
    }

    public function buildStateMachineFromBot(Bot $bot) {
        return $this->buildStateMachineFromModel($bot, 'payment_state');
    }


    public function buildStates() {
        // build states
        return [
            BotPaymentState::NONE     => new BotPaymentState(BotPaymentState::NONE),
            BotPaymentState::OK       => new BotPaymentState(BotPaymentState::OK),
            BotPaymentState::NOTICE   => new BotPaymentState(BotPaymentState::NOTICE),
            BotPaymentState::SOON     => new BotPaymentState(BotPaymentState::SOON),
            BotPaymentState::URGENT   => new BotPaymentState(BotPaymentState::URGENT),
            BotPaymentState::PAST_DUE => new BotPaymentState(BotPaymentState::PAST_DUE),
        ];



    }

    // add transitions
    public function addTransitionsToStates($states) {

        // reset * => BotPaymentState::OK
        foreach ([BotPaymentState::NONE, BotPaymentState::NOTICE, BotPaymentState::SOON, BotPaymentState::URGENT, BotPaymentState::PAST_DUE] as $source_state) {
            $this->addTransitionToStates($states, $source_state, BotPaymentState::OK, BotPaymentStateEvent::ENTERED_OK, new EnteredOK());
        }



        // BotPaymentState::OK => BotPaymentState::NOTICE
        foreach ([BotPaymentState::OK] as $source_state) {
            $this->addTransitionToStates($states, $source_state, BotPaymentState::NOTICE, BotPaymentStateEvent::ENTERED_NOTICE, new EnteredNotice());
        }

        // BotPaymentState::OK, BotPaymentState::NOTICE => BotPaymentState::SOON
        foreach ([BotPaymentState::OK, BotPaymentState::NOTICE] as $source_state) {
            $this->addTransitionToStates($states, $source_state, BotPaymentState::SOON, BotPaymentStateEvent::ENTERED_SOON, new EnteredSoon());
        }

        // BotPaymentState::OK, BotPaymentState::NOTICE, BotPaymentState::SOON => BotPaymentState::URGENT
        foreach ([BotPaymentState::OK, BotPaymentState::NOTICE, BotPaymentState::SOON] as $source_state) {
            $this->addTransitionToStates($states, $source_state, BotPaymentState::URGENT, BotPaymentStateEvent::ENTERED_URGENT, new EnteredUrgent());
        }

        // BotPaymentState::OK, BotPaymentState::NOTICE, BotPaymentState::SOON, BotPaymentState::URGENT => BotPaymentState::PAST_DUE
        foreach ([BotPaymentState::OK, BotPaymentState::NOTICE, BotPaymentState::SOON, BotPaymentState::URGENT] as $source_state) {
            $this->addTransitionToStates($states, $source_state, BotPaymentState::PAST_DUE, BotPaymentStateEvent::ENTERED_PAST_DUE, new EnteredPastDue());
        }


        return $states;
    }


}
