<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Support\Facades\DB;
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
        $xchain_notification = $swap->transaction['xchain_notification'];

        $swap_config = $swap->getSwapConfig();
        list($desired_quantity, $desired_asset) = $swap_config->getStrategy()->buildSwapOutputQuantityAndAsset($swap_config, $xchain_notification['quantity']);

        $actual_quantity = $bot->getBalance($desired_asset);

        return ($actual_quantity >= $desired_quantity);
    }
}
