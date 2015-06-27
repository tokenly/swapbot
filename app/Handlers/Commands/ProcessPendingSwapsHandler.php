<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ProcessPendingSwaps;
use Swapbot\Models\Data\SwapState;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Processor\SwapProcessor;

class ProcessPendingSwapsHandler {

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
     * @param  ProcessPendingSwaps  $command
     * @return void
     */
    public function handle(ProcessPendingSwaps $command)
    {
        // $all_swaps = $this->swap_repository->findAll();
        // $debug_swaps_text = '';
        // foreach($all_swaps as $all_swap) {
        //     $debug_swaps_text .= "Swap {$all_swap['id']}: {$all_swap['name']} - state: {$all_swap['state']}\n";
        // }

        $swaps = $this->swap_repository->findByStates(SwapState::allPendingStates());
        
        foreach($swaps as $swap) {
            $this->swap_repository->executeWithLockedSwap($swap, function($locked_swap) {
                if ($locked_swap->isPending()) {
                    // handle this swap
                    $this->swap_processor->processSwap($locked_swap);
                }
            });
        }
    }

}
