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
class FuelDepleted extends SwapCommand {

    /**
     */
    public function __invoke(Swap $swap)
    {
        // update the bot state in the database
        $this->updateSwapState($swap, SwapState::OUT_OF_FUEL);
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Fuel Depleted';
    }


}
