<?php

namespace Swapbot\Statemachines\SwapCommand;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\SwapWasConfirmed;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Swap;
use Swapbot\Statemachines\SwapCommand\SwapCommand;


/*
* SwapCommand
*/
class SwapConfirmed extends SwapCommand {

    /**
     */
    public function __invoke(Swap $swap)
    {
        // update the bot state in the database
        $this->updateSwapState($swap, SwapState::READY);

        // fire an event
        Event::fire(new SwapWasConfirmed($swap));
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Swap Confirmed';
    }


}
