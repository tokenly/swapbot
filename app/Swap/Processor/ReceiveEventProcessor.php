<?php

namespace Swapbot\Swap\Processor;

use ArrayObject;
use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Commands\UpdateBotBalances;
use Swapbot\Commands\UpdateBotPaymentAccount;
use Swapbot\Models\BotEvent;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\TransactionRepository;
use Swapbot\Statemachines\BotStateMachineFactory;
use Swapbot\Swap\Logger\BotEventLogger;
use Swapbot\Swap\Processor\ReceivePaymentProcessor;
use Swapbot\Swap\Processor\SwapProcessor;
use Tokenly\LaravelEventLog\Facade\EventLog;

class ReceiveEventProcessor {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, TransactionRepository $transaction_repository, SwapProcessor $swap_processor, ReceivePaymentProcessor $receive_payment_processor, BotEventLogger $bot_event_logger)
    {
        $this->bot_repository            = $bot_repository;
        $this->transaction_repository    = $transaction_repository;
        $this->swap_processor            = $swap_processor;
        $this->bot_event_logger          = $bot_event_logger;
        $this->receive_payment_processor = $receive_payment_processor;
    }


    public function handleReceive($xchain_notification) {
        // find a bot for this notification if it is received on a public address
        $bot = $this->bot_repository->findByPublicMonitorID($xchain_notification['notifiedAddressId']);
        if ($bot) { return $this->handlePublicAddressReceive($xchain_notification, $bot); }

        // find a bot for this notification if it is received on the payment address
        $bot = $this->bot_repository->findByPaymentMonitorID($xchain_notification['notifiedAddressId']);
        if ($bot) { return $this->receive_payment_processor->handlePaymentAddressReceive($xchain_notification, $bot); }

        // this was for a bot that doesn't exist
        EventLog::logError('receive.error', ['reason' => 'no bot found', 'notificationId' => $xchain_notification['notificationId']]);
        throw new Exception("Unable to find bot for monitor {$xchain_notification['notifiedAddressId']}.  notificationId was {$xchain_notification['notificationId']}", 1);
    }

    public function handlePublicAddressReceive($xchain_notification, $bot) {
        $tx_process = DB::transaction(function() use ($xchain_notification, $bot) {

            // load or create a new transaction from the database
            $transaction_model = $this->findOrCreateTransaction($xchain_notification, $bot['id']);
            if (!$transaction_model) { throw new Exception("Unable to access database", 1); }

            // initialize a DTO (data transfer object) to hold all the variables
            $tx_process = new ArrayObject([
                'transaction'               => $transaction_model,
                'xchain_notification'       => $xchain_notification,
                'bot'                       => $bot,
                'statemachine'              => $bot->stateMachine(),

                'confirmations'             => $xchain_notification['confirmations'],
                'is_confirmed'              => $xchain_notification['confirmed'],
                'destination'               => $xchain_notification['sources'][0],

                'tx_is_handled'             => false,
                'transaction_update_vars'   => [],
                'should_update_bot_balance' => ($xchain_notification['confirmed'] ? true : false),
                // 'bot_balances_delta'     => [],

                'any_processing_errors'     => false,
                'any_notification_given'    => false,
            ]);

            // previously processed transactions
            $this->checkForPreviouslyProcessedTransaction($tx_process);

            // check for incoming fuel transaction
            $this->checkForIncomingFuelTransaction($tx_process);

            // check for blacklisted sources
            $this->checkForBlacklistedAddresses($tx_process);

            // check bot state
            $this->checkBotState($tx_process);

            // process all swaps
            $this->processSwaps($tx_process);

            // done going through swaps - update the transaction
            $this->updateTransaction($tx_process);

            return $tx_process;
        });

        // // bot balance update must be done outside of the transaction
        // if ($tx_process['should_update_bot_balance']) {
        //     $this->updateBotBalance($tx_process['bot']);
        // }
    }

    public function handlePaymentAddressReceive($xchain_notification, $bot) {
        DB::transaction(function() use ($xchain_notification, $bot) {
            // initialize a DTO (data transfer object) to hold all the variables
            $receive_process = new ArrayObject([
                'xchain_notification' => $xchain_notification,
                'bot'                 => $bot,
            ]);

            return $receive_process;
        });

    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // balance
    
    // protected function updateBotBalance($bot) {
    //     try {
    //         $this->dispatch(new UpdateBotBalances($bot));
    //     } catch (Exception $e) {
    //         // log any failure
    //         EventLog::logError('balanceupdate.failed', $e);
    //         $this->bot_event_logger->logBalanceUpdateFailed($bot, $e);
    //     }
    // }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Transaction
    
    protected function findOrCreateTransaction($xchain_notification, $bot_id) {
        $transaction_model = $this->transaction_repository->findByTransactionIDAndBotIDWithLock($xchain_notification['txid'], $bot_id);
        if ($transaction_model) { return $transaction_model; }

        // create a new transaction
        return $this->transaction_repository->create([
            'txid'                => $xchain_notification['txid'],
            'bot_id'              => $bot_id,
            'xchain_notification' => $xchain_notification,
        ]);
    }




    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // checks

    protected function checkForPreviouslyProcessedTransaction($tx_process) {
        if ($tx_process['tx_is_handled']) { return; }

        if ($tx_process['transaction']['processed']) {
            $this->bot_event_logger->logToBotEvents($tx_process['bot'], 'tx.previous', BotEvent::LEVEL_DEBUG, [
                'msg'  => "Transaction {$tx_process['xchain_notification']['txid']} has already been processed.  Ignoring it.",
                'txid' => $tx_process['xchain_notification']['txid']
            ]);

            $tx_process['tx_is_handled']          = true;
            $tx_process['any_notification_given'] = true;
        }
    }    

    // check for blacklisted sources
    protected function checkForBlacklistedAddresses($tx_process) {
        if ($tx_process['tx_is_handled']) { return; }

        $blacklist_addresses = $tx_process['bot']['blacklist_addresses'];

        // never process a transaction coming from the same address that is receiving it
        $blacklist_addresses[] = $tx_process['xchain_notification']['notifiedAddress'];

        // never process a transaction coming from the payment address
        $blacklist_addresses[] = $tx_process['bot']['payment_address'];

        if (in_array($tx_process['xchain_notification']['sources'][0], $blacklist_addresses)) {
            // blacklisted
            $this->bot_event_logger->logSendFromBlacklistedAddress($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['is_confirmed']);

            $tx_process['tx_is_handled']                            = true;
            $tx_process['transaction_update_vars']['processed']     = true;
            $tx_process['transaction_update_vars']['confirmations'] = $tx_process['confirmations'];
            $tx_process['any_notification_given']                   = true;

        }

    }

    // is this a fuel top-up?
    protected function checkForIncomingFuelTransaction($tx_process) {
        if ($tx_process['tx_is_handled']) { return; }


        if ($tx_process['xchain_notification']['asset'] == 'BTC' AND in_array($tx_process['bot']['payment_address'], $tx_process['xchain_notification']['sources'])) {
            // this is a fuel transaction
            $this->bot_event_logger->logFuelTXReceived($tx_process['bot'], $tx_process['xchain_notification']);

            $tx_process['tx_is_handled']                        = true;
            $tx_process['transaction_update_vars']['processed'] = true;
            $tx_process['transaction_update_vars']['confirmations'] = $tx_process['confirmations'];
            $tx_process['any_notification_given']               = true;

            // update the bot's balance in memory only
            $tx_process['bot']->modifyBalance($tx_process['xchain_notification']['asset'], $tx_process['xchain_notification']['quantity']);
            // Log::debug('balances: '.json_encode($tx_process['bot']['balances'], 192));

            // the bot state might have changed, so check it now
            $this->dispatch(new ReconcileBotState($tx_process['bot']));

        }

    }

    protected function checkBotState($tx_process) {
        if ($tx_process['tx_is_handled']) { return; }

        $bot_state = $tx_process['statemachine']->getCurrentState();
        // Log::debug('bot_state: '.$bot_state->getName());
        if (!$bot_state->isActive()) {
            switch ($bot_state->getName()) {
                case BotState::INACTIVE:
                    // this bot is inactive
                    $this->bot_event_logger->logInactiveBotState($tx_process['bot'], $tx_process['xchain_notification'], $bot_state);
                    break;
                
                default:
                    // this bot is inactive
                    $this->bot_event_logger->logInactiveBotState($tx_process['bot'], $tx_process['xchain_notification'], $bot_state);
                    break;
                
            }

            $tx_process['tx_is_handled']          = true;
            $tx_process['any_notification_given'] = true;

            // a manually inactive bot still marks the transaction as processed
            if ($bot_state->getName() == BotState::INACTIVE) {
                $tx_process['transaction_update_vars']['processed'] = true;
                $tx_process['transaction_update_vars']['confirmations'] = $tx_process['confirmations'];
            }
        }
    }

    protected function processSwaps($tx_process) {
        if ($tx_process['tx_is_handled']) { return; }

        $bot = $tx_process['bot'];

        $any_swap_processed     = false;
        $all_matched_swaps_sent = true;
        foreach ($bot['swaps'] as $swap_config) {
            $was_processed = false;
            $was_sent      = false;

            // only process if the incoming asset matches the swap config
            if ($tx_process['xchain_notification']['asset'] == $swap_config['in']) {
                $swap = $this->swap_processor->processSwapConfig($swap_config, $tx_process['bot']['id'], $tx_process['transaction']['id']);
                if ($swap) {
                    $any_swap_processed = true;
                    $was_processed = true;
                    $was_sent = $swap->wasSent();
                }
            }

            if (!$was_sent AND $was_processed) {
                // this is a swap that needs to be sent, but was not sent yet
                $all_matched_swaps_sent = false;
            }
        }
        if (!$any_swap_processed) { $all_matched_swaps_sent = false; }

        if ($any_swap_processed AND $all_matched_swaps_sent) {
            // debit the bot's payment account (when any transaction was processed)
            $debit_amount = $bot->getTXFee();
            $bot_event = $this->bot_event_logger->logTransactionFee($bot, $debit_amount, $tx_process['transaction']['id']);
            $this->dispatch(new UpdateBotPaymentAccount($bot, $debit_amount, $is_credit=false, $bot_event));

            // save the billing event id
            $tx_process['transaction_update_vars']['billed_event_id'] = $bot_event['id'];

            // mark this transaction as processed (completed)
            $tx_process['transaction_update_vars']['processed']     = true;
            $tx_process['transaction_update_vars']['confirmations'] = $tx_process['confirmations'];

            $tx_process['any_notification_given'] = true;
        } else if (!$any_swap_processed) {
            // we received an asset, but no swap was processed
            $this->bot_event_logger->logUnknownReceiveTransaction($bot, $tx_process['xchain_notification']);
            $tx_process['any_notification_given'] = true;

            // mark the transaction as processed
            //   this was probably a transaction to fill up the bot
            $tx_process['transaction_update_vars']['processed']     = true;
            $tx_process['transaction_update_vars']['confirmations'] = $tx_process['confirmations'];
        }

    }

    protected function updateTransaction($tx_process) {
        if ($tx_process['transaction_update_vars']) {

            $update_vars = $tx_process['transaction_update_vars'];

            // mark the transaction as processed only if there were no errros
            // if (!$tx_process['any_processing_errors']) { $update_vars['processed'] = true; }

            $this->transaction_repository->update($tx_process['transaction'], $update_vars);
        }
    }


}
