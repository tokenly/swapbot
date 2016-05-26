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
use Swapbot\Swap\DateProvider\Facade\DateProvider;
use Swapbot\Swap\Tokenpass\Facade\TokenpassHandler;
use Swapbot\Swap\Util\RequestIDGenerator;


/*
* FirstMonthlyFeePaid
*/
class FirstMonthlyFeePaid extends BotCommand {

    /**
     */
    public function __invoke(Bot $bot)
    {
        DB::transaction(function() use ($bot) {
            // log and debit the month fee
            $AMOUNT = 1;
            $bot_event = $this->getBotEventLogger()->logFirstMonthlyFeePaid($bot, $AMOUNT, 'SWAPBOTMONTH');
            $this->getBotLedgerEntryRepository()->addDebit($bot, $AMOUNT, 'SWAPBOTMONTH', $bot_event);

            // add a lease
            $bot_lease_entry_repository = $this->getBotLeaseEntryRepository();
            $new_lease = $bot_lease_entry_repository->addNewLease($bot, $bot_event, DateProvider::now());
            $this->getBotEventLogger()->logLeaseCreated($bot, $new_lease);

            // update the bot state in the database
            $this->updateBotState($bot, BotState::LOW_FUEL);

            // move the initial fuel
            $this->moveInitialFuel($bot);

            // register the bot with Tokenpass
            TokenpassHandler::registerBotWithTokenpass($bot);
            
        });
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return 'First Monthly Fee Paid';
    }


    // move initial fuel from the swapbot reserve
    //   to this bot's public address
    protected function moveInitialFuel($bot) {
        // call XChain to move the initial fuel
        $payment_address_id = Config::get('swapbot.xchain_fuel_pool_address_id');
        $destination = $bot['address'];
        $quantity = $bot->getStartingBTCFuel();
        $asset = 'BTC';
        $fee = Config::get('swapbot.defaultFee');
        try {
            $request_id = RequestIDGenerator::generateSendHash('initialfuel'.','.$bot['uuid'], $destination, $quantity, $asset);
            $send_result = $this->getXChainClient()->sendConfirmed($payment_address_id, $destination, $quantity, $asset, $fee, null, $request_id);

            // log event
            $bot_event = $this->getBotEventLogger()->logMoveInitialFuelTXCreated($bot, $quantity, $asset, $destination, $fee, $send_result['txid']);
           
        } catch (Exception $e) {
            $this->getBotEventLogger()->logMoveInitialFuelTXFailed($bot, $e);
        }
    }

}
