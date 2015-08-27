<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileBotPaymentState;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Commands\UpdateBotPaymentAccount;
use Swapbot\Events\BotPaymentAccountUpdated;
use Swapbot\Models\Bot;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Swap\Logger\BotEventLogger;

class UpdateBotPaymentAccountHandler {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotEventLogger $bot_event_logger, BotLedgerEntryRepository $bot_ledger_entry_repository)
    {
        $this->bot_event_logger            = $bot_event_logger;
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
            $this->bot_ledger_entry_repository->addCredit($command->bot, $command->amount, $command->asset, $command->bot_event);

            // also credit BTC dust
            if ($command->btc_dust !== null) {
                $this->bot_ledger_entry_repository->addCredit($command->bot, $command->btc_dust, 'BTC', $command->bot_event);
            }

            // purchase SWAPBOTMONTH credits
            $this->purchaseSwapbotCredits($command->bot);
        } else {
            $this->bot_ledger_entry_repository->addDebit($command->bot, $command->amount, $command->asset, $command->bot_event);
        }

        // the bot state might have changed, so check it now
        $this->dispatch(new ReconcileBotState($command->bot));
        
        // the bot payment state might have changed, so also check it now
        $this->dispatch(new ReconcileBotPaymentState($command->bot));
        
        // fire an event
        Event::fire(new BotPaymentAccountUpdated($command->bot, $command->amount, $command->asset));
    }


    protected function purchaseSwapbotCredits(Bot $bot) {
        $payment_plan = $bot->getPaymentPlan();
        $balances_by_asset = $this->bot_ledger_entry_repository->sumCreditsAndDebitsByAsset($bot);
        foreach($balances_by_asset as $asset => $amount) {
            $purchase_details = $payment_plan->calculateMonthlyPurchaseDetails($amount, $asset);
            if ($purchase_details AND $purchase_details['months'] > 0) {
                // purchase month(s)
                $bot_event = $this->bot_event_logger->logMonthlyFeePurchased($bot, $purchase_details['months'], $purchase_details['cost'], $purchase_details['asset']);
                $this->bot_ledger_entry_repository->addDebit($bot, $purchase_details['cost'], $purchase_details['asset'], $bot_event);
                $this->bot_ledger_entry_repository->addCredit($bot, $purchase_details['months'], 'SWAPBOTMONTH', $bot_event);
            }
        }
    }

}
