<?php

namespace Swapbot\Swap\Processor;

use ArrayObject;
use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReceiveBotPayment;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\BotLedgerEntryRepository;
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
    public function __construct(BotEventLogger $swap_event_logger, BotLedgerEntryRepository $bot_ledger_entry_repository, TransactionRepository $transaction_repository)
    {
        $this->swap_event_logger           = $swap_event_logger;
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
        $this->transaction_repository      = $transaction_repository;
    }


    public function handlePaymentAddressReceive($xchain_notification, $bot) {
        DB::transaction(function() use ($xchain_notification, $bot) {
            // load or create a new transaction from the database
            $transaction_model = $this->findOrCreateTransaction($xchain_notification['txid'], $bot['id']);
            if (!$transaction_model) { throw new Exception("Unable to access database", 1); }

            // initialize a DTO (data transfer object) to hold all the variables
            $tx_process = new ArrayObject([
                'bot'                       => $bot,
                'transaction'               => $transaction_model,
                'xchain_notification'       => $xchain_notification,

                'confirmations'             => $xchain_notification['confirmations'],
                'is_confirmed'              => $xchain_notification['confirmed'],

                'should_update_transaction' => false,
                'any_processing_errors'     => false,

                'should_process_payment'    => true,
            ]);

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

    protected function validatePayment($tx_process) {
        if ($tx_process['should_process_payment']) {
            // make sure this is BTC
            if ($tx_process['xchain_notification']['asset'] != 'BTC') {
                $this->swap_event_logger->logUnknownPaymentTransaction($tx_process['bot'], $tx_process['xchain_notification']);

                $tx_process['should_process_payment'] = false;
                $tx_process['should_update_transaction'] = true;
            }
        }
    }

    protected function handlePayment($tx_process) {
        if ($tx_process['should_process_payment']) {
            // fire off a payment command

            // sanity check
            if ($tx_process['xchain_notification']['asset'] != 'BTC') { throw new Exception("Only BTC accepted", 1); }

            $amount = $tx_process['xchain_notification']['quantity'];
            $bot_event = $this->swap_event_logger->logConfirmedPaymentTx($tx_process['bot'], $tx_process['xchain_notification']);
            $is_credit = true;
            $this->dispatch(new ReceiveBotPayment($tx_process['bot'], $amount, $is_credit, $bot_event));

            $tx_process['should_update_transaction'] = true;
        }
    }



    protected function findOrCreateTransaction($txid, $bot_id) {
        $transaction_model = $this->transaction_repository->findByTransactionIDAndBotIDWithLock($txid, $bot_id);
        if ($transaction_model) { return $transaction_model; }

        // create a new transaction
        return $this->transaction_repository->create(['bot_id' => $bot_id, 'txid' => $txid]);
    }

    protected function updateTransaction($tx_process) {
        if ($tx_process['should_update_transaction']) {
            $update_vars = [
                'confirmations' => $tx_process['confirmations'],
            ];

            // mark the transaction as processed only if there were no errros
            if (!$tx_process['any_processing_errors']) { $update_vars['processed'] = true; }

            $this->transaction_repository->update($tx_process['transaction'], $update_vars);
        }
    }

}
