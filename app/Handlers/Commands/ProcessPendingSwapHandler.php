<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ProcessPendingSwap;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Processor\SwapProcessor;

class ProcessPendingSwapHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(SwapRepository $swap_repository, SwapProcessor $swap_processor)
    {
        $this->swap_repository = $swap_repository;
        $this->swap_processor  = $swap_processor;
    }


    /**
     * Handle the command.
     *
     * @param  ProcessPendingSwap  $command
     * @return void
     */
    public function handle(ProcessPendingSwap $command)
    {
        $swap = $command->swap;

        $this->swap_repository->executeWithLockedSwap($swap, function($locked_swap) {
            if ($locked_swap->isPending()) {
                // process the swap
                $this->swap_processor->processSwap($locked_swap);
            }
        });
    }

}
