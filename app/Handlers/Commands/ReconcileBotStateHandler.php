<?php namespace Swapbot\Handlers\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStateEvent;
use Swapbot\Repositories\BotLeaseEntryRepository;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Statemachines\BotStateMachineFactory;
use Swapbot\Swap\DateProvider\Facade\DateProvider;
use Swapbot\Swap\Logger\BotEventLogger;
use Swapbot\Swapbot\ShutdownHandler;

class ReconcileBotStateHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, BotLedgerEntryRepository $bot_ledger_entry_repository, BotLeaseEntryRepository $bot_lease_entry_repository, BotEventLogger $bot_event_logger, ShutdownHandler $shutdown_handler)
    {
        $this->bot_repository              = $bot_repository;
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
        $this->bot_lease_entry_repository  = $bot_lease_entry_repository;
        $this->bot_event_logger            = $bot_event_logger;
        $this->shutdown_handler            = $shutdown_handler;
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

        $this->bot_repository->executeWithLockedBot($bot, function ($locked_bot) {
            $loop_again = true;

            while ($loop_again) {
                $loop_again = false;
                switch ($locked_bot['state']) {
                    case BotState::BRAND_NEW:
                        if ($this->paymentAddressHasEnoughForMonthlyFee($locked_bot)) {
                            $locked_bot->stateMachine()->triggerEvent(BotStateEvent::FIRST_MONTHLY_FEE_PAID);

                            // loop again to allow the low fuel state to be processed once
                            $loop_again = true;
                        }
                        break;

                    case BotState::LOW_FUEL:
                        if ($this->publicAddressHasEnoughFuel($locked_bot)) {
                            $locked_bot->stateMachine()->triggerEvent(BotStateEvent::FUELED);

                            // loop again
                            $loop_again = true;
                        }
                        break;

                    case BotState::ACTIVE:
                        // check monthly fee
                        if ($this->monthlyFeeHasExpired($locked_bot)) {
                            $locked_bot->stateMachine()->triggerEvent(BotStateEvent::LEASE_EXPIRED);

                            // loop again
                            $loop_again = true;
                            break;
                        }

                        // check out of fuel
                        if (!$this->publicAddressHasEnoughFuel($locked_bot)) {
                            $locked_bot->stateMachine()->triggerEvent(BotStateEvent::FUEL_EXHAUSTED);
                        }
                        break;

                    case BotState::PAYING:
                        if ($this->paymentAddressHasEnoughForMonthlyFee($locked_bot)) {
                            $locked_bot->stateMachine()->triggerEvent(BotStateEvent::MONTHLY_FEE_PAID);
                        } else {
                            $locked_bot->stateMachine()->triggerEvent(BotStateEvent::PAYMENT_EXHAUSTED);
                        }

                        // loop again
                        $loop_again = true;
                        break;

                    case BotState::UNPAID:
                        if ($this->paymentAddressHasEnoughForMonthlyFee($locked_bot)) {
                            $locked_bot->stateMachine()->triggerEvent(BotStateEvent::MONTHLY_FEE_PAID);

                            // loop again
                            $loop_again = true;
                        }
                        break;

                    case BotState::SHUTTING_DOWN:
                        if ($this->shutdown_handler->botHasPassedShutdownBlock($locked_bot)) {
                            // send funds
                            $refunded = $this->shutdown_handler->refundBot($locked_bot);

                            if ($refunded) {
                                // change state
                                $locked_bot->stateMachine()->triggerEvent(BotStateEvent::COMPLETE_SHUTDOWN);

                                // log shutdown complete
                                $this->bot_event_logger->logBotShutdownComplete($locked_bot);
                            }
                        }
                        break;
                }
            }

        });

    }


    protected function paymentAddressHasEnoughForMonthlyFee($bot) {
        $balance = $this->bot_ledger_entry_repository->sumCreditsAndDebits($bot, 'SWAPBOTMONTH');
        if ($balance >= 1) {
            return true;
        }

        return false;
    }

    protected function monthlyFeeHasExpired($bot) {
        // need to check the monthly fee dates...
        $lease = $this->bot_lease_entry_repository->getLastEntryForBot($bot);
        if (!$lease) { return true; }

        // if the lease end date is now or earlier, then the lease has expired
        if (Carbon::parse($lease['end_date'])->lte(DateProvider::now())) {
            return true;
        }

        return false;
    }

    protected function publicAddressHasEnoughFuel($bot) {
        // Log::debug("publicAddressHasEnoughFuel BTC balance: {$bot['balances']['BTC']}  \$bot->getMinimumBTCFuel()=".$bot->getMinimumBTCFuel()." returning ".json_encode(isset($bot['balances']['BTC']) AND $bot['balances']['BTC'] >= $bot->getMinimumBTCFuel(), 192));
        return isset($bot['balances']['BTC']) AND $bot['balances']['BTC'] >= $bot->getMinimumBTCFuel();
    }

}
