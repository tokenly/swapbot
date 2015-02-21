<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\ReceiveBotPayment;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Repositories\BotLedgerEntryRepository;

class ReceiveBotPaymentHandler {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotLedgerEntryRepository $bot_ledger_entry_repository)
    {
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
    }

    /**
     * Handle the command.
     *
     * @param  ReceiveBotPayment  $command
     * @return void
     */
    public function handle(ReceiveBotPayment $command)
    {
        // add a ledger entry
        $this->bot_ledger_entry_repository->addCredit($command->bot, $command->amount, $command->bot_event);

        // the bot state might have changed, so check it now
        $this->dispatch(new ReconcileBotState($command->bot));
        
        


    }

}
