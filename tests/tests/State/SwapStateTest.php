<?php

use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Data\SwapStateEvent;
use \PHPUnit_Framework_Assert as PHPUnit;

class SwapStateTest extends TestCase {

    protected $use_database = true;

    public function testSwapStateTransitions()
    {
        app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($this);

        // install xchain mocks
        app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        ////////////////////////////////////////////////////////////////////////
        // BRAND_NEW -> OUT_OF_STOCK -> READY -> SENT -> COMPLETE

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

        // transition to sent (directly from ready)
        $state_machine->triggerEvent(SwapStateEvent::SWAP_SENT);
        $this->checkState($swap, SwapState::SENT);

        // transition to complete
        $state_machine->triggerEvent(SwapStateEvent::SWAP_COMPLETED);
        $this->checkState($swap, SwapState::COMPLETE);


        ////////////////////////////////////////////////////////////////////////
        // READY

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);


        ////////////////////////////////////////////////////////////////////////
        // READY -> ERROR -> READY

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
        // READY -> OUT_OF_STOCK

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // go to out of stock
        $state_machine->triggerEvent(SwapStateEvent::STOCK_DEPLETED);
        $this->checkState($swap, SwapState::OUT_OF_STOCK);

        ////////////////////////////////////////////////////////////////////////
        // BRAND_NEW -> OUT_OF_FUEL

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // go to out of fuel
        $state_machine->triggerEvent(SwapStateEvent::FUEL_DEPLETED);
        $this->checkState($swap, SwapState::OUT_OF_FUEL);


        ////////////////////////////////////////////////////////////////////////
        // READY -> OUT_OF_FUEL -> READY

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // go to out of fuel
        $state_machine->triggerEvent(SwapStateEvent::FUEL_DEPLETED);
        $this->checkState($swap, SwapState::OUT_OF_FUEL);

        // transition with fuel checked
        $state_machine->triggerEvent(SwapStateEvent::FUEL_CHECKED);
        $this->checkState($swap, SwapState::READY);


        ////////////////////////////////////////////////////////////////////////
        // READY -> CONFIRMING -> CONFIRMING -> READY

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // confirming
        $state_machine->triggerEvent(SwapStateEvent::CONFIRMING);
        $this->checkState($swap, SwapState::CONFIRMING);

        // confirming (again)
        $state_machine->triggerEvent(SwapStateEvent::CONFIRMING);
        $this->checkState($swap, SwapState::CONFIRMING);

        // confirmed
        $state_machine->triggerEvent(SwapStateEvent::CONFIRMED);
        $this->checkState($swap, SwapState::READY);



        ////////////////////////////////////////////////////////////////////////
        // READY -> REFUNDED -> COMPLETE

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // transition to refunded
        $state_machine->triggerEvent(SwapStateEvent::SWAP_REFUND);
        $this->checkState($swap, SwapState::REFUNDED);

        // transition to complete
        $state_machine->triggerEvent(SwapStateEvent::SWAP_COMPLETED);
        $this->checkState($swap, SwapState::COMPLETE);


        ////////////////////////////////////////////////////////////////////////
        // REFUNDED -> READY (reset)

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // transition to refunded
        $state_machine->triggerEvent(SwapStateEvent::SWAP_REFUND);
        $this->checkState($swap, SwapState::REFUNDED);

        // transition back to READY
        $state_machine->triggerEvent(SwapStateEvent::SWAP_RESET);
        $this->checkState($swap, SwapState::READY);



        ////////////////////////////////////////////////////////////////////////
        // SENT -> READY (reset)

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // transition to sent (directly from ready)
        $state_machine->triggerEvent(SwapStateEvent::SWAP_SENT);
        $this->checkState($swap, SwapState::SENT);

        // transition back to READY
        $state_machine->triggerEvent(SwapStateEvent::SWAP_RESET);
        $this->checkState($swap, SwapState::READY);


        ////////////////////////////////////////////////////////////////////////
        // READY -> OUT_OF_STOCK -> REFUNDED

        // make a sample swap and state machine
        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition with stock checked
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // go to out of stock
        $state_machine->triggerEvent(SwapStateEvent::STOCK_DEPLETED);
        $this->checkState($swap, SwapState::OUT_OF_STOCK);

        // go to refunded
        $state_machine->triggerEvent(SwapStateEvent::SWAP_REFUND);
        $this->checkState($swap, SwapState::REFUNDED);



        ////////////////////////////////////////////////////////////////////////
        // READY -> PERMANENT_ERROR -> READY

        $swap = app('SwapHelper')->newSampleSwap();
        $state_machine = $swap->statemachine();

        // transition to ready
        $state_machine->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $this->checkState($swap, SwapState::READY);

        // transition to error
        $state_machine->triggerEvent(SwapStateEvent::SWAP_PERMANENTLY_ERRORED);
        $this->checkState($swap, SwapState::PERMANENT_ERROR);
        
        // transition back to ready
        $state_machine->triggerEvent(SwapStateEvent::SWAP_RETRY);
        $this->checkState($swap, SwapState::READY);
        

    }


    protected function checkState($swap, $expected_state) {
        PHPUnit::assertEquals($expected_state, $swap->stateMachine()->getCurrentState()->getName(), "Unexpected state");

        // reload the swap from the DB
        $db_swap = app('Swapbot\Repositories\SwapRepository')->findByID($swap['id']);
        PHPUnit::assertEquals($expected_state, $db_swap->stateMachine()->getCurrentState()->getName(), "Unexpected state");
        

    }

}
