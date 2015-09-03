<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Swapbot\Commands\ShutdownBot;
use Swapbot\Models\Data\BotStateEvent;
use Swapbot\Repositories\BlockRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\Logger\Facade\BotEventLogger;

class ShutdownBotHandler
{
    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, BlockRepository $block_repository)
    {
        $this->bot_repository   = $bot_repository;
        $this->block_repository = $block_repository;
    }

    /**
     * Handle the command.
     *
     * @param  ShutdownBot  $command
     * @return void
     */
    public function handle(ShutdownBot $command)
    {
        $bot = $command->bot;
        $shutdown_address = $command->shutdown_address;
        $shutdown_delay = $command->shutdown_delay;

        // get the current block height
        $block_height = $this->block_repository->findBestBlockHeight();
        $shutdown_block = $block_height + $shutdown_delay;


        $this->bot_repository->executeWithLockedBot($bot, function($locked_bot) use ($shutdown_block, $shutdown_address) {
            // find next block

            // update the shutdown address and shutdown completion block
            $this->bot_repository->update($locked_bot, [
                'shutdown_address' => $shutdown_address,
                'shutdown_block'   => $shutdown_block,
            ]);

            BotEventLogger::logBotShutdownBegan($locked_bot, $shutdown_block, $shutdown_address);

            // move the bot to the shutting down state
            $locked_bot->stateMachine()->triggerEvent(BotStateEvent::START_SHUTDOWN);

        });
    }
}
