<?php

namespace Swapbot\Swap\Processor;

use ArrayObject;
use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Commands\UpdateBotPaymentAccount;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\TransactionRepository;
use Swapbot\Swap\Logger\BotEventLogger;
use Tokenly\LaravelEventLog\Facade\EventLog;

class ReceivePaymentProcessor {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, BotEventLogger $swap_event_logger, BotLedgerEntryRepository $bot_ledger_entry_repository, TransactionRepository $transaction_repository)
    {
        $this->bot_repository              = $bot_repository;
        $this->swap_event_logger           = $swap_event_logger;
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
        $this->transaction_repository      = $transaction_repository;
    }


    public function handlePaymentAddressReceive($xchain_notification, $bot) {
        $this->bot_repository->executeWithLockedBot($bot, function($bot) use ($xchain_notification) {
            // load or create a new transaction from the database
            $transaction_model = $this->findOrCreateTransaction($xchain_notification, $bot['id'], 'receive');
            if (!$transaction_model) { throw new Exception("Unable to access database", 1); }

            // initialize a DTO (data transfer object) to hold all the variables
            $tx_process = new ArrayObject([
                'bot'                        => $bot,
                'transaction'                => $transaction_model,
                'xchain_notification'        => $xchain_notification,

                'confirmations'              => $xchain_notification['confirmations'],
                'is_confirmed'               => $xchain_notification['confirmed'],

                'should_update_transaction'  => false,
                'should_reconcile_bot_state' => true,

                'should_process_payment'     => true,
            ]);

            // previously processed transactions
            $this->checkForPreviouslyProcessedTransaction($tx_process);

            // handle an unconfirmed TX
            $this->handleUnconfirmedTX($tx_process);

            // validate payment
            $this->validatePayment($tx_process);

            // handle payment
            $this->handlePayment($tx_process);

            // done handling payment
            $this->updateTransaction($tx_process);

            return $tx_process;
        });

    }




    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // checks

    protected function handleUnconfirmedTX($tx_process) {
        // is this an unconfirmed tx?
        if (!$tx_process['is_confirmed']) {
            $tx_process['should_process_payment'] = false;
            $this->swap_event_logger->logUnconfirmedPaymentTx($tx_process['bot'], $tx_process['xchain_notification']);
        }
    }

    protected function checkForPreviouslyProcessedTransaction($tx_process) {
        if (!$tx_process['should_process_payment']) { return; }

        if ($tx_process['transaction']['processed']) {
            $this->swap_event_logger->logPreviousPaymentTransaction($tx_process['bot'], $tx_process['xchain_notification']);
            $tx_process['should_process_payment']    = false;
            $tx_process['should_update_transaction'] = true;
        }
    }    

    protected function validatePayment($tx_process) {
        if (!$tx_process['should_process_payment']) { return; }

        // make sure this is an asset we accept
        $bot = $tx_process['bot'];
        $payment_plan = $bot->getPaymentPlan();
        $asset = $tx_process['xchain_notification']['asset'];

        if (!$payment_plan->isAssetAccepted($asset)) {
            $this->swap_event_logger->logUnknownPaymentTransaction($tx_process['bot'], $tx_process['xchain_notification']);

            $tx_process['should_process_payment'] = false;
            $tx_process['should_update_transaction'] = true;
        }
    }

    protected function handlePayment($tx_process) {
        if (!$tx_process['should_process_payment']) { return; }

        // fire off a payment command
        $amount = $tx_process['xchain_notification']['quantity'];
        $asset = $tx_process['xchain_notification']['asset'];
        $bot_event = $this->swap_event_logger->logConfirmedPaymentTx($tx_process['bot'], $tx_process['xchain_notification']);
        $is_credit = true;
        $this->dispatch(new UpdateBotPaymentAccount($tx_process['bot'], $amount, $asset, $is_credit, $bot_event));

        $tx_process['should_update_transaction'] = true;
    }



    protected function findOrCreateTransaction($xchain_notification, $bot_id, $type) {
        return $this->transaction_repository->findOrCreateTransaction($xchain_notification['txid'], $bot_id, $type, ['xchain_notification' => $xchain_notification]);
    }

    protected function updateTransaction($tx_process) {
        if ($tx_process['should_update_transaction']) {
            $update_vars = [
                'confirmations' => $tx_process['confirmations'],
                'processed'     => true,
            ];

            $this->transaction_repository->update($tx_process['transaction'], $update_vars);
        }
    }

    protected function handleReconcileBotState($tx_process) {
        if ($tx_process['should_reconcile_bot_state']) {
            // the bot state might have changed, so check it now
            $this->dispatch(new ReconcileBotState($tx_process['bot']));
        }
    }

}
