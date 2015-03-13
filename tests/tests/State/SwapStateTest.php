<?php

use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Data\SwapStateEvent;
use \PHPUnit_Framework_Assert as PHPUnit;

class SwapStateTest extends TestCase {

    protected $use_database = true;

    public function testSwapStateTransitions()
    {
        // install xchain mocks
        app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // no transition yet
        $this->checkState($swap, SwapState::BRAND_NEW);

        // transition with stock depleted
        $state_machine->triggerEvent(SwapStateEvent::STOCK_DEPLETED);
        $this->checkState($swap, SwapState::OUT_OF_STOCK);

        // transition back to ready
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // transition to sent
        $state_machine->triggerEvent(SwapStateEvent::SWAP_SENT);
        $this->checkState($swap, SwapState::SENT);

        // transition to complete
        $state_machine->triggerEvent(SwapStateEvent::SWAP_COMPLETED);
        $this->checkState($swap, SwapState::COMPLETE);

        // transition to ready state
        // $state_machine->triggerEvent(SwapStateEvent::STOCK_DEPLETED);


        // // bot state is updated in the db
        // PHPUnit::assertEquals(SwapState::LOW_FUEL, $swap['state']);
        // PHPUnit::assertEquals(SwapState::LOW_FUEL, app('Swapbot\Repositories\SwapRepository')->findByID($swap['id'])['state']);

        // // receive payment again (OK)
        // $state_machine->triggerEvent(SwapStateEvent::CREATION_FEE_PAID);

        // // still low fuel
        // PHPUnit::assertEquals(SwapState::LOW_FUEL, $state_machine->getCurrentState()->getName());


        // // receive fuel
        // $state_machine->triggerEvent(SwapStateEvent::FUELED);

        // // bot is now active
        // PHPUnit::assertEquals(SwapState::ACTIVE, $state_machine->getCurrentState()->getName());


        ////////////////////////////////////////////////////////////////////////
        
        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);


        ////////////////////////////////////////////////////////////////////////
        
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition to ready
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // transition to error
        $state_machine->triggerEvent(SwapStateEvent::SWAP_ERRORED);
        $this->checkState($swap, SwapState::ERROR);
        
        // transition back to ready
        $state_machine->triggerEvent(SwapStateEvent::SWAP_RETRY);
        $this->checkState($swap, SwapState::READY);
        

        ////////////////////////////////////////////////////////////////////////
        
        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // go to out of stock
        $state_machine->triggerEvent(SwapStateEvent::STOCK_DEPLETED);
        $this->checkState($swap, SwapState::OUT_OF_STOCK);


    }


    protected function checkState($swap, $expected_state) {
        PHPUnit::assertEquals($expected_state, $swap->stateMachine()->getCurrentState()->getName(), "Unexpected state");

        // reload the swap from the DB
        $db_swap = app('Swapbot\Repositories\SwapRepository')->findByID($swap['id']);
        PHPUnit::assertEquals($expected_state, $db_swap->stateMachine()->getCurrentState()->getName(), "Unexpected state");
        

    }

}
