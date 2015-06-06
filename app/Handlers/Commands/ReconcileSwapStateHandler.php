<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileSwapState;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Data\SwapStateEvent;
use Swapbot\Swap\Logger\BotEventLogger;

class ReconcileSwapStateHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotEventLogger $bot_event_logger)
    {
        $this->bot_event_logger            = $bot_event_logger;

    }

    /**
     * Handle the command.
     *
     * @param  ReconcileSwapState  $command
     * @return void
     */
    public function handle(ReconcileSwapState $command)
    {
        $swap = $command->swap;

        DB::transaction(function () use ($swap) {
            switch ($swap['state']) {
                case SwapState::BRAND_NEW:
                case SwapState::OUT_OF_STOCK:
                    if ($this->swapBalanceIsSufficient($swap)) {
                        $swap->stateMachine()->triggerEvent(SwapStateEvent::STOCK_CHECKED);
                    } else if ($swap['state'] == SwapState::BRAND_NEW) {
                        $swap->stateMachine()->triggerEvent(SwapStateEvent::STOCK_DEPLETED);
                    }
                    break;

                // case SwapState::CONFIRMING:
                //     if (!$this->swapHasBeenConfirmed($swap)) {
                //         $swap->stateMachine()->triggerEvent(SwapStateEvent::CONFIRMED);
                //     }
                //     break;
                
                case SwapState::READY:
                    if (!$this->swapBalanceIsSufficient($swap)) {
                        $swap->stateMachine()->triggerEvent(SwapStateEvent::STOCK_DEPLETED);
                    }
                    break;
            }
        });
    }



    public function swapBalanceIsSufficient($swap) {
        $bot = $swap->bot;

        $desired_quantity = $swap['receipt']['quantityOut'];
        $desired_asset    = $swap['receipt']['assetOut'];

        $actual_quantity = $bot->getBalance($desired_asset);

        return ($actual_quantity >= $desired_quantity);
    }
}
