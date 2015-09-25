<?php

namespace Swapbot\Statemachines\SwapCommand;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\SwapWasPermanentlyErrored;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Swap;
use Swapbot\Statemachines\SwapCommand\SwapCommand;


/*
* SwapPermanentlyErrored command
*/
class SwapPermanentlyErrored extends SwapCommand {

    /**
     */
    public function __invoke(Swap $swap)
    {
        // update the bot state in the database
        $this->updateSwapState($swap, SwapState::PERMANENT_ERROR);

        // fire an event
        Event::fire(new SwapWasPermanentlyErrored($swap));
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Swap Permanently Errored';
    }


}
