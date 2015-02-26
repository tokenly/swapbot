<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\UpdateBotPaymentAccount;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Events\BotPaymentAccountUpdated;
use Swapbot\Repositories\BotLedgerEntryRepository;

class UpdateBotPaymentAccountHandler {

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
     * @param  UpdateBotPaymentAccount  $command
     * @return void
     */
    public function handle(UpdateBotPaymentAccount $command)
    {
        // add a ledger entry
        if ($command->is_credit) {
            $this->bot_ledger_entry_repository->addCredit($command->bot, $command->amount, $command->bot_event);
        } else {
            $this->bot_ledger_entry_repository->addDebit($command->bot, $command->amount, $command->bot_event);
        }

        // the bot state might have changed, so check it now
        $this->dispatch(new ReconcileBotState($command->bot));
        
        // fire an event
        Event::fire(new BotPaymentAccountUpdated($command->bot, $command->amount));
    }

}
