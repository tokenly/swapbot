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
use Swapbot\Swap\Logger\BotEventLogger;
use Tokenly\LaravelEventLog\Facade\EventLog;

class SendEventProcessor {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, TransactionRepository $transaction_repository, BotEventLogger $swap_event_logger)
    {
        $this->bot_repository         = $bot_repository;
        $this->transaction_repository = $transaction_repository;
        $this->swap_event_logger      = $swap_event_logger;
    }


    public function handleSend($xchain_notification) {
        // find the bot related to this notification
        $bot = $this->bot_repository->findBySendMonitorID($xchain_notification['notifiedAddressId']);
        if (!$bot) { throw new Exception("Unable to find bot for monitor {$xchain_notification['notifiedAddressId']}", 1); }

        // lock the transaction
        $should_update_bot_balance = null;
        $bot = DB::transaction(function() use ($xchain_notification, $bot, &$should_update_bot_balance) {

            // load or create a new transaction from the database
            $transaction_model = $this->findOrCreateTransaction($xchain_notification['txid'], $bot['id']);
            if (!$transaction_model) { throw new Exception("Unable to access database", 1); }

            $is_confirmed = $xchain_notification['confirmed'];

            // setup variables
            $should_process            = true;
            $should_update_transaction = false;
            $should_update_bot_balance = ($is_confirmed ? true : false);


            // previously processed
            if ($should_process AND $transaction_model['processed']) {
                $this->swap_event_logger->logToBotEvents($bot, 'send.previous', BotEvent::LEVEL_DEBUG, [
                    'msg'  => "Send transaction {$xchain_notification['txid']} has already been processed.  Ignoring it.",
                    'txid' => $xchain_notification['txid']
                ]);
                $should_process = false;
            }


            // just log it
            if ($should_process) {
                // determine the number of confirmations
                $confirmations = $xchain_notification['confirmations'];
                $quantity = $xchain_notification['quantity'];
                $asset = $xchain_notification['asset'];
                $destination = $xchain_notification['destinations'][0];

                if ($is_confirmed AND !$transaction_model['processed']) {
                    $this->swap_event_logger->logConfirmedSendTx($bot, $xchain_notification, $destination, $quantity, $asset, $confirmations);
                    $should_update_transaction = true;
                } else {
                    $this->swap_event_logger->logUnconfirmedSendTx($bot, $xchain_notification, $destination, $quantity, $asset);
                }

                if ($should_update_transaction) {
                    // mark the transaction as processed
                    $update_vars = [];
                    $update_vars['processed'] = true;
                    $update_vars['confirmations'] = $confirmations;

                    $this->transaction_repository->update($transaction_model, $update_vars);
                }
            }


            return $bot;

        });


        // bot balance update must be done outside of the transaction
        if ($should_update_bot_balance) {
            $this->updateBotBalance($bot);
        }

        return $bot;
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
    


}
