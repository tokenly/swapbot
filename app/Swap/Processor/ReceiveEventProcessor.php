<?php

namespace Swapbot\Swap\Processor;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\UpdateBotBalances;
use Swapbot\Models\BotEvent;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\TransactionRepository;
use Swapbot\Swap\Logger\SwapEventLogger;
use Tokenly\LaravelEventLog\Facade\EventLog;
use ArrayObject;

class ReceiveEventProcessor {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, TransactionRepository $transaction_repository, SwapProcessor $swap_processor, SwapEventLogger $swap_event_logger)
    {
        $this->bot_repository         = $bot_repository;
        $this->transaction_repository = $transaction_repository;
        $this->swap_processor         = $swap_processor;
        $this->swap_event_logger      = $swap_event_logger;
    }


    public function handleReceive($xchain_notification) {
        $tx_process = DB::transaction(function() use ($xchain_notification) {

            // find the bot related to this notification
            $bot = $this->bot_repository->findByReceiveMonitorID($xchain_notification['notifiedAddressId']);
            if (!$bot) { throw new Exception("Unable to find bot for monitor {$xchain_notification['notifiedAddressId']}", 1); }

            // load or create a new transaction from the database
            $transaction_model = $this->findOrCreateTransaction($xchain_notification['txid'], $bot['id']);
            if (!$transaction_model) { throw new Exception("Unable to access database", 1); }

            // initialize a DTO (data transfer object) to hold all the variables
            $tx_process = new ArrayObject([
                'transaction'               => $transaction_model,
                'xchain_notification'       => $xchain_notification,
                'bot'                       => $bot,

                'confirmations'             => $xchain_notification['confirmations'],
                'is_confirmed'              => $xchain_notification['confirmed'],
                'destination'               => $xchain_notification['sources'][0],
                'swap_receipts'             => $transaction_model['swap_receipts'],

                'should_process_swaps'      => true,
                'should_update_transaction' => false,
                'should_update_bot_balance' => ($xchain_notification['confirmed'] ? true : false),

                'any_processing_errors'     => false,
                'any_notification_given'    => false,
            ]);

            // previously processed bots
            $this->checkForPreviouslyProcessedTransaction($tx_process);

            // check for blacklisted sources (for confirmed transactions)
            $this->checkForBlacklistedAddresses($tx_process);

            // process all swaps
            $this->processSwaps($tx_process);

            // done going through swaps - update the swap receipts
            $this->updateTransaction($tx_process);

            // if the transaction was not handled, then log that as an event
            $this->handleNoNotification($tx_process);

            return $tx_process;
        });

        // bot balance update must be done outside of the transaction
        if ($tx_process['should_update_bot_balance']) {
            $this->updateBotBalance($tx_process['bot']);
        }
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Desc
    
    protected function updateBotBalance($bot) {
        try {
            $this->dispatch(new UpdateBotBalances($bot));
        } catch (Exception $e) {
            // log any failure
            EventLog::logError('balanceupdate.failed', $e);
            $this->swap_event_logger->logBalanceUpdateFailed($bot, $e);
        }
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Desc
    
    protected function findOrCreateTransaction($txid, $bot_id) {
        $transaction_model = $this->transaction_repository->findByTransactionIDAndBotIDWithLock($txid, $bot_id);
        if ($transaction_model) { return $transaction_model; }

        // create a new transaction
        return $this->transaction_repository->create(['bot_id' => $bot_id, 'txid' => $txid]);
    }




    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Desc

    protected function checkForPreviouslyProcessedTransaction($tx_process) {
        if ($tx_process['should_process_swaps'] AND $tx_process['transaction']['processed']) {
            $this->swap_event_logger->logToBotEvents($tx_process['bot'], 'tx.previous', BotEvent::LEVEL_DEBUG, [
                'msg'  => "Transaction {$tx_process['xchain_notification']['txid']} has already been processed.  Ignoring it.",
                'txid' => $tx_process['xchain_notification']['txid']
            ]);

            $tx_process['should_process_swaps']   = false;
            $tx_process['any_notification_given'] = true;
        }
    }    

    protected function checkForBlacklistedAddresses($tx_process) {

        if ($tx_process['should_process_swaps'] AND !$tx_process['transaction']['processed']) {
            $blacklist_addresses = $tx_process['bot']['blacklist_addresses'];

            // never send to self
            $blacklist_addresses[] = $tx_process['xchain_notification']['notifiedAddress'];

            if (in_array($tx_process['xchain_notification']['sources'][0], $blacklist_addresses)) {
                // blacklisted
                $this->swap_event_logger->logSendFromBlacklistedAddress($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['is_confirmed']);

                $tx_process['should_process_swaps']      = false;
                $tx_process['should_update_transaction'] = true;
                $tx_process['any_notification_given']    = true;

            }
        }

    }

    protected function processSwaps($tx_process) {
        if (!$tx_process['should_process_swaps']) { return; }

        $any_swap_processed = false;
        foreach ($tx_process['bot']['swaps'] as $swap) {
            $swap_processed = $this->swap_processor->processSwap($swap, $tx_process);
            if ($swap_processed) { $any_swap_processed = true; }
        }

        if (!$any_swap_processed) {
            // we received an asset, but no swap was processed
            $this->swap_event_logger->logUnknownReceiveTransaction($tx_process['bot'], $tx_process['xchain_notification']);
            $tx_process['any_notification_given'] = true;

            // mark the transaction as processed
            //   this is probably an attempt to fill up the bot
            $tx_process['should_update_transaction'] = true;
        }

    }

    protected function updateTransaction($tx_process) {
        if ($tx_process['should_update_transaction']) {
            $update_vars = [
                'swap_receipts' => $tx_process['swap_receipts'],
                'confirmations' => $tx_process['confirmations'],
            ];

            // mark the transaction as processed only if there were no errros
            if (!$tx_process['any_processing_errors']) { $update_vars['processed'] = true; }

            $this->transaction_repository->update($tx_process['transaction'], $update_vars);
        }
    }

    protected function handleNoNotification($tx_process) {
        if (!$tx_process['any_notification_given']) {
            // no feedback was given to the user
            //   this should never happen
            $this->swap_event_logger->logUnhandledTransaction($tx_process['bot'], $tx_process['xchain_notification']);
        }
    }

}
