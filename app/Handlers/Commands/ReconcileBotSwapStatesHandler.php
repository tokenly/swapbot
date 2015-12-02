<?php

namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileBotSwapStates;
use Swapbot\Commands\ReconcileSwapState;
use Swapbot\Models\Data\SwapState;
use Swapbot\Repositories\SwapRepository;

class ReconcileBotSwapStatesHandler {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(SwapRepository $swap_repository)
    {
        $this->swap_repository = $swap_repository;
    }

    /**
     * Handle the command.
     *
     * @param  ReconcileBotSwapStates  $command
     * @return void
     */
    public function handle(ReconcileBotSwapStates $command)
    {
        $bot = $command->bot;
        $block_height = $command->block_height;

        DB::transaction(function () use ($bot, $block_height) {
            $states = [SwapState::OUT_OF_STOCK, SwapState::OUT_OF_FUEL];
            $swaps = $this->swap_repository->findByBotIDWithStates($bot['id'], $states);
            
            foreach($swaps as $swap) {
                $this->dispatch(new ReconcileSwapState($swap, $block_height));
            }
        });
    }


}
