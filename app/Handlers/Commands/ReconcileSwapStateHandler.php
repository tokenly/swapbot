<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileSwapState;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Data\SwapStateEvent;
use Swapbot\Providers\Accounts\Facade\AccountHandler;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Logger\BotEventLogger;

class ReconcileSwapStateHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(SwapRepository $swap_repository, BotEventLogger $bot_event_logger)
    {
        $this->swap_repository  = $swap_repository;
        $this->bot_event_logger = $bot_event_logger;

    }

    /**
     * Handle the command.
     *
     * @param  ReconcileSwapState  $command
     * @return void
     */
    public function handle(ReconcileSwapState $command)
    {
        $swap         = $command->swap;
        $block_height = $command->block_height;

        DB::transaction(function () use ($swap, $block_height) {
            $this->swap_repository->executeWithLockedSwap($swap, function($locked_swap) use ($block_height) {
                switch ($locked_swap['state']) {
                    case SwapState::BRAND_NEW:
                        if ($locked_swap['state'] == SwapState::BRAND_NEW) {
                            // move the initial incoming funds
                            AccountHandler::moveIncomingReceivedFunds($locked_swap);

                            // move the stock
                            $stock_allocated = AccountHandler::allocateStock($locked_swap);

                            if ($stock_allocated) {
                                // stock has been allocated to complete this swap
                                $locked_swap->stateMachine()->triggerEvent(SwapStateEvent::STOCK_CHECKED);
                            } else {
                                // not enough stock
                                $locked_swap->stateMachine()->triggerEvent(SwapStateEvent::STOCK_DEPLETED);
                            }
                        }
                        break;

                    case SwapState::OUT_OF_STOCK:
                        $stock_allocated = AccountHandler::allocateStock($locked_swap);

                        if ($stock_allocated) {
                            // stock has been allocated to complete this swap
                            $locked_swap->stateMachine()->triggerEvent(SwapStateEvent::STOCK_CHECKED);
                        }
                        break;
                }
            });
        });
    }

}
