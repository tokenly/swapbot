<?php

namespace Swapbot\Statemachines;

use Exception;
use Illuminate\Support\Facades\Log;
use MetaborStd\Event\EventInterface;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Data\SwapStateEvent;
use Swapbot\Models\Swap;
use Swapbot\Statemachines\StateMachineFactory;
use Swapbot\Statemachines\SwapCommand\FuelChecked;
use Swapbot\Statemachines\SwapCommand\FuelDepleted;
use Swapbot\Statemachines\SwapCommand\StockChecked;
use Swapbot\Statemachines\SwapCommand\StockDepleted;
use Swapbot\Statemachines\SwapCommand\SwapCompleted;
use Swapbot\Statemachines\SwapCommand\SwapConfirmed;
use Swapbot\Statemachines\SwapCommand\SwapConfirming;
use Swapbot\Statemachines\SwapCommand\SwapErrored;
use Swapbot\Statemachines\SwapCommand\SwapInvalidated;
use Swapbot\Statemachines\SwapCommand\SwapPermanentlyErrored;
use Swapbot\Statemachines\SwapCommand\SwapRefund;
use Swapbot\Statemachines\SwapCommand\SwapReset;
use Swapbot\Statemachines\SwapCommand\SwapRetry;
use Swapbot\Statemachines\SwapCommand\SwapSent;

/*
* SwapStateMachineFactory
*/
class SwapStateMachineFactory extends StateMachineFactory {

    public function __construct() {
    }

    public function buildStateMachineFromSwap(Swap $swap) {
        return $this->buildStateMachineFromModel($swap);
    }


    public function buildStates() {
        // build states
        return [
            SwapState::BRAND_NEW       => new SwapState(SwapState::BRAND_NEW),
            SwapState::READY           => new SwapState(SwapState::READY),
            SwapState::CONFIRMING      => new SwapState(SwapState::CONFIRMING),
            SwapState::OUT_OF_STOCK    => new SwapState(SwapState::OUT_OF_STOCK),
            SwapState::OUT_OF_FUEL     => new SwapState(SwapState::OUT_OF_FUEL),
            SwapState::SENT            => new SwapState(SwapState::SENT),
            SwapState::REFUNDED        => new SwapState(SwapState::REFUNDED),
            SwapState::COMPLETE        => new SwapState(SwapState::COMPLETE),
            SwapState::ERROR           => new SwapState(SwapState::ERROR),
            SwapState::PERMANENT_ERROR => new SwapState(SwapState::PERMANENT_ERROR),
            SwapState::INVALIDATED     => new SwapState(SwapState::INVALIDATED),
        ];

    }


    // add transitions
    public function addTransitionsToStates($states) {
        
        /* TEMPLATE
        $this->addTransitionToStates($states, SwapState::STARTINGSTATE, SwapState::ENDINGSTATE, SwapStateEvent::SWAPEVENT, new Command());
        */

        // SwapState::BRAND_NEW => SwapState::OUT_OF_STOCK with SwapStateEvent::STOCK_DEPLETED via StockDepleted
        $this->addTransitionToStates($states, SwapState::BRAND_NEW, SwapState::OUT_OF_STOCK, SwapStateEvent::STOCK_DEPLETED, new StockDepleted());

        // SwapState::BRAND_NEW => SwapState::OUT_OF_FUEL with SwapStateEvent::FUEL_DEPLETED via FuelDepleted
        $this->addTransitionToStates($states, SwapState::BRAND_NEW, SwapState::OUT_OF_FUEL, SwapStateEvent::FUEL_DEPLETED, new FuelDepleted());

        // SwapState::BRAND_NEW => SwapState::READY with SwapStateEvent::STOCK_CHECKED via StockChecked
        $this->addTransitionToStates($states, SwapState::BRAND_NEW, SwapState::READY, SwapStateEvent::STOCK_CHECKED, new StockChecked());

        // SwapState::OUT_OF_STOCK => SwapState::READY with SwapStateEvent::STOCK_CHECKED via StockChecked
        $this->addTransitionToStates($states, SwapState::OUT_OF_STOCK, SwapState::READY, SwapStateEvent::STOCK_CHECKED, new StockChecked());

        // SwapState::OUT_OF_FUEL => SwapState::READY with SwapStateEvent::FUEL_CHECKED via StockChecked
        $this->addTransitionToStates($states, SwapState::OUT_OF_FUEL, SwapState::READY, SwapStateEvent::FUEL_CHECKED, new FuelChecked());

        // SwapState::READY => SwapState::CONFIRMING with SwapStateEvent::CONFIRMING via SwapConfirming
        $this->addTransitionToStates($states, SwapState::READY, SwapState::CONFIRMING, SwapStateEvent::CONFIRMING, new SwapConfirming());

        // SwapState::CONFIRMING => SwapState::READY with SwapStateEvent::CONFIRMED via SwapConfirmed
        $this->addTransitionToStates($states, SwapState::CONFIRMING, SwapState::READY, SwapStateEvent::CONFIRMED, new SwapConfirmed());

        // SwapState::CONFIRMING => SwapState::CONFIRMING with SwapStateEvent::CONFIRMING via SwapConfirming
        $this->addTransitionToStates($states, SwapState::CONFIRMING, SwapState::CONFIRMING, SwapStateEvent::CONFIRMING, new SwapConfirming());

        // SwapState::READY => SwapState::SENT with SwapStateEvent::SWAP_SENT via SwapSent
        $this->addTransitionToStates($states, SwapState::READY, SwapState::SENT, SwapStateEvent::SWAP_SENT, new SwapSent());

        // SwapState::SENT => SwapState::COMPLETE with SwapStateEvent::SWAP_COMPLETED via Command
        $this->addTransitionToStates($states, SwapState::SENT, SwapState::COMPLETE, SwapStateEvent::SWAP_COMPLETED, new SwapCompleted());

        // SwapState::READY => SwapState::ERROR with SwapStateEvent::SWAP_ERRORED via SwapErrored
        $this->addTransitionToStates($states, SwapState::READY, SwapState::ERROR, SwapStateEvent::SWAP_ERRORED, new SwapErrored());

        // SwapState::ERROR => SwapState::READY with SwapStateEvent::SWAP_RETRY via SwapRetry
        $this->addTransitionToStates($states, SwapState::ERROR, SwapState::READY, SwapStateEvent::SWAP_RETRY, new SwapRetry());

        // SwapState::READY => SwapState::OUT_OF_STOCK with SwapStateEvent::STOCK_DEPLETED via StockDepleted
        $this->addTransitionToStates($states, SwapState::READY, SwapState::OUT_OF_STOCK, SwapStateEvent::STOCK_DEPLETED, new StockDepleted());

        // SwapState::READY => SwapState::OUT_OF_FUEL with SwapStateEvent::FUEL_DEPLETED via FuelDepleted
        $this->addTransitionToStates($states, SwapState::READY, SwapState::OUT_OF_FUEL, SwapStateEvent::FUEL_DEPLETED, new FuelDepleted());


        // SwapState::READY => SwapState::REFUNDED with SwapStateEvent::SWAP_REFUND via SwapRefund
        $this->addTransitionToStates($states, SwapState::READY, SwapState::REFUNDED, SwapStateEvent::SWAP_REFUND, new SwapRefund());

        // SwapState::REFUNDED => SwapState::COMPLETE with SwapStateEvent::SWAP_COMPLETED via Command
        $this->addTransitionToStates($states, SwapState::REFUNDED, SwapState::COMPLETE, SwapStateEvent::SWAP_COMPLETED, new SwapCompleted());


        // SwapState::REFUNDED => SwapState::READY with SwapStateEvent::SWAP_RESET via SwapReset
        $this->addTransitionToStates($states, SwapState::REFUNDED, SwapState::READY, SwapStateEvent::SWAP_RESET, new SwapReset());

        // SwapState::SENT => SwapState::READY with SwapStateEvent::SWAP_RESET via SwapReset
        $this->addTransitionToStates($states, SwapState::SENT, SwapState::READY, SwapStateEvent::SWAP_RESET, new SwapReset());


        // SwapState::OUT_OF_STOCK => SwapState::REFUNDED with SwapStateEvent::SWAP_REFUND via SwapRefund
        $this->addTransitionToStates($states, SwapState::OUT_OF_STOCK, SwapState::REFUNDED, SwapStateEvent::SWAP_REFUND, new SwapRefund());


        // SwapState::READY => SwapState::PERMANENT_ERROR with SwapStateEvent::SWAP_PERMANENTLY_ERRORED via SwapPermanentlyErrored
        $this->addTransitionToStates($states, SwapState::READY, SwapState::PERMANENT_ERROR, SwapStateEvent::SWAP_PERMANENTLY_ERRORED, new SwapPermanentlyErrored());

        // SwapState::PERMANENT_ERROR => SwapState::READY with SwapStateEvent::SWAP_RETRY via SwapRetry
        $this->addTransitionToStates($states, SwapState::PERMANENT_ERROR, SwapState::READY, SwapStateEvent::SWAP_RETRY, new SwapRetry());



        // SwapState::READY => SwapState::INVALIDATED with SwapStateEvent::SWAP_WAS_INVALIDATED via SwapPermanentlyErrored
        $this->addTransitionToStates($states, SwapState::READY, SwapState::INVALIDATED, SwapStateEvent::SWAP_WAS_INVALIDATED, new SwapInvalidated());

        // SwapState::OUT_OF_STOCK => SwapState::INVALIDATED with SwapStateEvent::SWAP_WAS_INVALIDATED via SwapPermanentlyErrored
        $this->addTransitionToStates($states, SwapState::OUT_OF_STOCK, SwapState::INVALIDATED, SwapStateEvent::SWAP_WAS_INVALIDATED, new SwapInvalidated());

        // SwapState::CONFIRMING => SwapState::INVALIDATED with SwapStateEvent::SWAP_WAS_INVALIDATED via SwapPermanentlyErrored
        $this->addTransitionToStates($states, SwapState::CONFIRMING, SwapState::INVALIDATED, SwapStateEvent::SWAP_WAS_INVALIDATED, new SwapInvalidated());


        return $states;
    }


}
