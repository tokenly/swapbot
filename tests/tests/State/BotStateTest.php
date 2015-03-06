<?php

use Metabor\Statemachine\Process;
use Metabor\Statemachine\Statemachine;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStateEvent;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotStateTest extends TestCase {

    protected $use_database = true;

    public function testBotStateTransitions()
    {
        // install xchain mocks
        app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        // make a sample bot
        $bot = app('BotHelper')->newSampleBot();

        // build a statemachine
        $state_machine = app('Swapbot\Statemachines\BotStateMachineFactory')->buildStateMachineFromBot($bot);

        // no transition yet
        PHPUnit::assertEquals(BotState::BRAND_NEW, $state_machine->getCurrentState()->getName());

        // transition
        $state_machine->triggerEvent(BotStateEvent::CREATION_FEE_PAID);

        // now we are in the low fuel state
        PHPUnit::assertEquals(BotState::LOW_FUEL, $state_machine->getCurrentState()->getName());

        // bot state is updated in the db
        PHPUnit::assertEquals(BotState::LOW_FUEL, $bot['state']);
        PHPUnit::assertEquals(BotState::LOW_FUEL, app('Swapbot\Repositories\BotRepository')->findByID($bot['id'])['state']);

        // receive payment again (OK)
        $state_machine->triggerEvent(BotStateEvent::CREATION_FEE_PAID);

        // still low fuel
        PHPUnit::assertEquals(BotState::LOW_FUEL, $state_machine->getCurrentState()->getName());


        // receive fuel
        $state_machine->triggerEvent(BotStateEvent::FUELED);

        // bot is now active
        PHPUnit::assertEquals(BotState::ACTIVE, $state_machine->getCurrentState()->getName());


        // fuel is exhausted
        $state_machine->triggerEvent(BotStateEvent::FUEL_EXHAUSTED);
        PHPUnit::assertEquals(BotState::LOW_FUEL, $state_machine->getCurrentState()->getName());

        // receive fuel
        $state_machine->triggerEvent(BotStateEvent::FUELED);
        PHPUnit::assertEquals(BotState::ACTIVE, $state_machine->getCurrentState()->getName());

        
        // go to unpaid state
        $state_machine->triggerEvent(BotStateEvent::PAYMENT_EXHAUSTED);
        PHPUnit::assertEquals(BotState::UNPAID, $state_machine->getCurrentState()->getName());


        // pay up
        $state_machine->triggerEvent(BotStateEvent::PAID);
        PHPUnit::assertEquals(BotState::ACTIVE, $state_machine->getCurrentState()->getName());



    }


}
