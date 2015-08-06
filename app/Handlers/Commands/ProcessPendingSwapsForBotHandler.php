<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ProcessPendingSwapsForBot;
use Swapbot\Models\Data\SwapState;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Processor\SwapProcessor;

class ProcessPendingSwapsForBotHandler {

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
     * @param  ProcessPendingSwapsForBot  $command
     * @return void
     */
    public function handle(ProcessPendingSwapsForBot $command)
    {
        $swaps = $this->swap_repository->findByBotIDWithStates($command->bot['id'], SwapState::allPendingStates());
        $block_height = $command->block_height;
        
        foreach($swaps as $swap) {
            $this->swap_repository->executeWithLockedSwap($swap, function($locked_swap) use ($block_height) {
                if ($locked_swap->isPending()) {
                    // handle this swap
                    $this->swap_processor->processSwap($locked_swap, $block_height);
                }
            });
        }
    }

}
