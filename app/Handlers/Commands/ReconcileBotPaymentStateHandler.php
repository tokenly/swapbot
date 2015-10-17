<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileBotPaymentState;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Models\Data\BotPaymentStateEvent;
use Swapbot\Repositories\BotLeaseEntryRepository;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\DateProvider\Facade\DateProvider;
use Swapbot\Swap\Logger\Facade\BotEventLogger;

class ReconcileBotPaymentStateHandler
{

    public static function inState($state, $arr) {
        return in_array($state, $arr);
    }

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, BotLedgerEntryRepository $bot_ledger_entry_repository, BotLeaseEntryRepository $bot_lease_entry_repository)
    {
        $this->bot_repository              = $bot_repository;
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
        $this->bot_lease_entry_repository  = $bot_lease_entry_repository;

    }

    /**
     * Handle the command.
     *
     * @param  ReconcileBotPaymentState  $command
     * @return void
     */
    public function handle(ReconcileBotPaymentState $command)
    {
        $bot = $command->bot;

        $this->bot_repository->executeWithLockedBot($bot, function ($locked_bot) {
            $last_lease_entry = $this->bot_lease_entry_repository->getLastEntryForBot($locked_bot);
            $swapbotmonth_quantity = $this->bot_ledger_entry_repository->sumCreditsAndDebits($locked_bot, 'SWAPBOTMONTH');
            $payment_expires_date = $locked_bot->calculatePaymentExpirationDate($last_lease_entry, $swapbotmonth_quantity);

            $loop_again = null;
            while ($loop_again === true OR $loop_again === null) {
                $loop_again = false;

                // BotPaymentState::NONE
                // BotPaymentState::PAST_DUE
                // BotPaymentState::URGENT
                // BotPaymentState::SOON
                // BotPaymentState::NOTICE
                // BotPaymentState::OK

                // BotPaymentStateEvent::ENTERED_OK
                // BotPaymentStateEvent::ENTERED_NOTICE
                // BotPaymentStateEvent::ENTERED_SOON
                // BotPaymentStateEvent::ENTERED_URGENT
                // BotPaymentStateEvent::ENTERED_PAST_DUE

                switch ($locked_bot['payment_state']) {
                    case BotPaymentState::NONE:
                        // automatically move to OK
                        $locked_bot->paymentStateMachine()->triggerEvent(BotPaymentStateEvent::ENTERED_OK);
                        $loop_again = true;
                        break;

                    case BotPaymentState::OK:
                    case BotPaymentState::NOTICE:
                    case BotPaymentState::SOON:
                    case BotPaymentState::URGENT:
                    case BotPaymentState::PAST_DUE:
                        // all states are handled here
                        $payment_state = $locked_bot['payment_state'];

                        if (self::inState($payment_state, [BotPaymentState::OK, BotPaymentState::NOTICE, BotPaymentState::SOON, BotPaymentState::URGENT])) {
                            // check for past due
                            if ($this->paymentIsPastDue($locked_bot, $payment_expires_date)) {
                                $locked_bot->paymentStateMachine()->triggerEvent(BotPaymentStateEvent::ENTERED_PAST_DUE);
                                break;
                            }
                        }

                        if (self::inState($payment_state, [BotPaymentState::OK, BotPaymentState::NOTICE, BotPaymentState::SOON])) {
                            // check for urgent
                            if ($this->paymentIsUrgent($locked_bot, $payment_expires_date)) {
                                $locked_bot->paymentStateMachine()->triggerEvent(BotPaymentStateEvent::ENTERED_URGENT);
                                break;
                            }
                        }

                        if (self::inState($payment_state, [BotPaymentState::OK, BotPaymentState::NOTICE])) {
                            // check for soon
                            if ($this->paymentIsSoon($locked_bot, $payment_expires_date)) {
                                $locked_bot->paymentStateMachine()->triggerEvent(BotPaymentStateEvent::ENTERED_SOON);
                                break;
                            }
                        }

                        if (self::inState($payment_state, [BotPaymentState::OK])) {
                            // check for notice
                            if ($this->paymentIsNotice($locked_bot, $payment_expires_date)) {
                                $locked_bot->paymentStateMachine()->triggerEvent(BotPaymentStateEvent::ENTERED_NOTICE);
                                break;
                            }
                        }

                        // if nothing was triggered above, then reset to ok
                        if (self::inState($payment_state, [BotPaymentState::NOTICE, BotPaymentState::SOON, BotPaymentState::URGENT, BotPaymentState::PAST_DUE])) {
                            if ($this->paymentIsOK($locked_bot, $payment_expires_date)) {
                                // payment date is ok because it passed all the checks above
                                $locked_bot->paymentStateMachine()->triggerEvent(BotPaymentStateEvent::ENTERED_OK);
                                break;
                            }
                        }

                        // no change
                }
            }

        });

    }


    protected function paymentIsPastDue($bot, $payment_expires_date) {
        return $this->paymentExpiresBeforeLength($bot, $payment_expires_date);
    }

    protected function paymentIsUrgent($bot, $payment_expires_date) {
        return $this->paymentExpiresBeforeLength($bot, $payment_expires_date, '1 day');
    }

    protected function paymentIsSoon($bot, $payment_expires_date) {
        return $this->paymentExpiresBeforeLength($bot, $payment_expires_date, '7 days');
    }

    protected function paymentIsNotice($bot, $payment_expires_date) {
        return $this->paymentExpiresBeforeLength($bot, $payment_expires_date, '14 days');
    }

    protected function paymentIsOK($bot, $payment_expires_date) {
        return $this->paymentExpiresAfterLength($bot, $payment_expires_date, '14 days');
    }

    protected function paymentExpiresBeforeLength($bot, $payment_expires_date, $length=null) {
        // if no payment exists, then it expires before any time
        if ($payment_expires_date === null) { return true; }

        $target_date = DateProvider::now();
        if ($length !== null) { $target_date->modify($length); }

        if ($payment_expires_date->lte($target_date)) {
            return true;
        }
        return false;
    }

    protected function paymentExpiresAfterLength($bot, $payment_expires_date, $length) {
        if ($payment_expires_date === null) { return false; }

        $target_date = DateProvider::now();
        if ($length !== null) { $target_date->modify($length); }

        if ($payment_expires_date->gt($target_date)) {
            return true;
        }
        return false;
    }

}
