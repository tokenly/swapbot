<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStateEvent;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Statemachines\BotStateMachineFactory;
use Swapbot\Swap\Logger\BotEventLogger;
use Tokenly\XChainClient\Client;

class ReconcileBotStateHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotLedgerEntryRepository $bot_ledger_entry_repository, BotEventLogger $bot_event_logger, Client $xchain_client)
    {
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
        $this->bot_event_logger            = $bot_event_logger;
        $this->xchain_client               = $xchain_client;

    }

    /**
     * Handle the command.
     *
     * @param  ReconcileBotState  $command
     * @return void
     */
    public function handle(ReconcileBotState $command)
    {
        $bot = $command->bot;

        DB::transaction(function () use ($bot) {
            $done = false;

            while (!$done) {
                $done = true;
                switch ($bot['state']) {
                    case BotState::BRAND_NEW:
                        if ($this->paymentAddressHasEnoughForCreationFee($bot)) {
                            // update the state
                            $bot->stateMachine()->triggerEvent(BotStateEvent::CREATION_FEE_PAID);

                            // loop again to allow the low fuel state to be processed once
                            $done = false;
                        }
                        break;

                    case BotState::LOW_FUEL:
                        if ($this->publicAddressHasEnoughFuel($bot)) {
                            // update the state
                            $bot->stateMachine()->triggerEvent(BotStateEvent::FUELED);

                            // loop again
                            $done = false;
                        }
                        break;

                    case BotState::ACTIVE:
                        if (!$this->paymentAddressHasEnoughForNextTransaction($bot)) {
                            // update the state to unpaid
                            $bot->stateMachine()->triggerEvent(BotStateEvent::PAYMENT_EXHAUSTED);
                        }
                        if (!$this->publicAddressHasEnoughFuel($bot)) {
                            // update the state to unfueled
                            $bot->stateMachine()->triggerEvent(BotStateEvent::FUEL_EXHAUSTED);
                        }
                        break;

                    case BotState::UNPAID:
                        if ($this->paymentAddressHasEnoughForNextTransaction($bot)) {
                            // update the state
                            $bot->stateMachine()->triggerEvent(BotStateEvent::PAID);

                            // loop again
                            $done = false;
                        }
                        break;
                }
            }

        });
    }


    protected function paymentAddressHasEnoughForCreationFee($bot) {
        $balance = $this->bot_ledger_entry_repository->sumCreditsAndDebits($bot);
        $fuel_needed = $bot->getStartingBTCFuel();
        $required = $bot->getPaymentPlan()->getCreationFee() + $fuel_needed;
        if ($balance >= $required) {
            return true;
        }

        return false;
    }

    protected function paymentAddressHasEnoughForNextTransaction($bot) {
        $balance = $this->bot_ledger_entry_repository->sumCreditsAndDebits($bot);
        $required = $bot->getPaymentPlan()->getTXFee();
        if ($balance >= $required) {
            return true;
        }

        return false;
    }

    protected function publicAddressHasEnoughFuel($bot) {
        // Log::debug("BTC balance: {$bot['balances']['BTC']}");
        return isset($bot['balances']['BTC']) AND $bot['balances']['BTC'] >= $bot->getMinimumBTCFuel();
    }

}
