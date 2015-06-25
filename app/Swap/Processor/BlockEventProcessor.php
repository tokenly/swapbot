<?php

namespace Swapbot\Swap\Processor;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Repositories\BotRepository;

class BlockEventProcessor {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository)
    {
        $this->bot_repository            = $bot_repository;
    }


    public function handleBlock($xchain_notification) {
        // bring all bots up to date
        foreach ($this->bot_repository->findAll() as $bot) {
            $this->dispatch(new ReconcileBotState($bot));
        }
    }

}
