<?php

namespace Swapbot\Statemachines\SwapCommand;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Swap;
use Swapbot\Statemachines\SwapCommand\SwapCommand;


/*
* SwapCommand
*/
class SwapCompleted extends SwapCommand {

    /**
     */
    public function __invoke(Swap $swap)
    {
        // update the bot state in the database
        $this->updateSwapState($swap, SwapState::COMPLETE);

        // reconcile the state again
        // $this->dispatch(new ReconcileSwapState($swap));
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Swap Completed';
    }


}
