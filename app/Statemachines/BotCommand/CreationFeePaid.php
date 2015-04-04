<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Statemachines\BotCommand\BotCommand;


/*
* CreationFeePaid
*/
class CreationFeePaid extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        DB::transaction(function() use ($bot) {
            // move the fuel
            $this->moveInitialFuel($bot);

            // log and debit the fee
            $amount = $bot->getCreationFee();
            $bot_event = $this->getBotEventLogger()->logInitialCreationFeePaid($bot, $amount, 'BTC');
            $this->getBotLedgerEntryRepository()->addDebit($bot, $amount, $bot_event);

            // update the bot state in the database
            $this->updateBotState($bot, BotState::LOW_FUEL);
        });
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'Creation Fee Paid';
    }


    protected function moveInitialFuel($bot) {
        $fuel_needed = $bot->getStartingBTCFuel();

        // call XChain
        $payment_address_id = $bot['payment_address_id'];
        $destination = $bot['address'];
        $quantity = $bot->getStartingBTCFuel();
        $asset = 'BTC';
        $fee = Config::get('swapbot.defaultFee');
        try {
            $send_result = $this->getXChainClient()->send($payment_address_id, $destination, $quantity, $asset, $fee);

            // log event
            $this->getBotEventLogger()->logMoveInitialFuelTXCreated($bot, $quantity, $asset, $destination, $fee, $send_result['txid']);
        } catch (Exception $e) {
            $this->getBotEventLogger()->logMoveInitialFuelTXFailed($bot, $e);
        }
    }

}
