<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Statemachines\BotCommand\BotCommand;


/*
* MonthlyFeePaid
*/
class MonthlyFeePaid extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        DB::transaction(function() use ($bot) {
            // log and debit the fee
            $amount = 1;
            $bot_event = $this->getBotEventLogger()->logMonthlyFeePaid($bot, $amount, 'SWAPBOTMONTH');
            $this->getBotLedgerEntryRepository()->addDebit($bot, $amount, 'SWAPBOTMONTH', $bot_event);

            // extend the lease
            $bot_lease_entry_repository = $this->getBotLeaseEntryRepository();
            $new_lease = $bot_lease_entry_repository->extendLease($bot, $bot_event);
            $this->getBotEventLogger()->logLeaseCreated($bot, $new_lease);

            // update the bot state in the database
            $this->updateBotState($bot, BotState::ACTIVE);
        });
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Monthly Fee Paid';
    }



}
