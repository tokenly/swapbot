<?php

use Metabor\Statemachine\Process;
use Metabor\Statemachine\Statemachine;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Models\Data\BotPaymentStateEvent;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotPaymentStateTest extends TestCase {

    protected $use_database = true;


    public function testBotPaymentStateTransitions()
    {
        app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($this);
        app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);
        $bot = app('BotHelper')->newSampleBot();

        // build a statemachine
        $state_machine = app('Swapbot\Statemachines\BotPaymentStateMachineFactory')->buildStateMachineFromBot($bot);

        // no transition yet
        PHPUnit::assertEquals(BotPaymentState::NONE, $state_machine->getCurrentState()->getName());

        // OK => NOTICE
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_NOTICE, BotPaymentState::NOTICE);

        // OK => SOON
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_SOON, BotPaymentState::SOON);

        // OK => URGENT
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_URGENT, BotPaymentState::URGENT);

        // OK => PAST_DUE
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_PAST_DUE, BotPaymentState::PAST_DUE);


        // NOTICE => SOON
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_NOTICE, BotPaymentState::NOTICE);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_SOON, BotPaymentState::SOON);

        // NOTICE => URGENT
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_NOTICE, BotPaymentState::NOTICE);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_URGENT, BotPaymentState::URGENT);

        // NOTICE => PAST_DUE
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_NOTICE, BotPaymentState::NOTICE);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_PAST_DUE, BotPaymentState::PAST_DUE);



        // SOON => URGENT
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_SOON, BotPaymentState::SOON);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_URGENT, BotPaymentState::URGENT);

        // SOON => PAST_DUE
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_SOON, BotPaymentState::SOON);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_PAST_DUE, BotPaymentState::PAST_DUE);



        // URGENT => PAST_DUE
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_URGENT, BotPaymentState::URGENT);
        $this->runTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_PAST_DUE, BotPaymentState::PAST_DUE);


    }

    protected function runTransitionTest($state_machine, $bot, $event, $new_state) {
        $state_machine->triggerEvent($event);

        // now we are in the low fuel state
        PHPUnit::assertEquals($new_state, $state_machine->getCurrentState()->getName());
        PHPUnit::assertEquals($new_state, $bot['payment_state']);
        PHPUnit::assertEquals($new_state, app('Swapbot\Repositories\BotRepository')->findByID($bot['id'])['payment_state']);
    }

}
