<?php

namespace Swapbot\Statemachines\SwapCommand;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Metabor\Statemachine\Command;
use Swapbot\Models\Swap;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Logger\BotEventLogger;


/*
* SwapCommand
*/
class SwapCommand extends Command {

    use DispatchesCommands;

    public function updateSwapState(Swap $swap, $new_state) {
        $this->getSwapRepository()->update($swap, ['state' => $new_state]);

        // log the new swap state as an event
        $this->getBotEventLogger()->logSwapStateChange($swap, $new_state);
    }

    public function getSwapRepository() {
        if (!isset($this->swap_repository)) { $this->swap_repository = app('Swapbot\Repositories\SwapRepository'); }
        return $this->swap_repository;
    }

    public function getBotEventLogger() {
        if (!isset($this->bot_event_logger)) { $this->bot_event_logger = app('Swapbot\Swap\Logger\BotEventLogger'); }
        return $this->bot_event_logger;
    }


}
