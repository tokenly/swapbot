<?php

namespace Swapbot\Swap\Processor;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\UpdateBotBalances;
use Swapbot\Models\BotEvent;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\TransactionRepository;
use Swapbot\Swap\Exception\SwapStrategyException;
use Swapbot\Swap\Factory\StrategyFactory;
use Swapbot\Swap\Logger\SwapEventLogger;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\Client;

class ReceiveEventProcessor {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, TransactionRepository $transaction_repository, Client $xchain_client, StrategyFactory $strategy_factory, SwapEventLogger $swap_event_logger)
    {
        $this->bot_repository         = $bot_repository;
        $this->transaction_repository = $transaction_repository;
        $this->xchain_client          = $xchain_client;
        $this->strategy_factory       = $strategy_factory;
        $this->swap_event_logger      = $swap_event_logger;
    }


    public function handleReceive($xchain_notification) {
        // find the bot related to this notification
        $bot = $this->bot_repository->findByReceiveMonitorID($xchain_notification['notifiedAddressId']);
        if (!$bot) { throw new Exception("Unable to find bot for monitor {$xchain_notification['notifiedAddressId']}", 1); }

        // lock the transaction
        $should_update_bot_balance = null;
        $bot = DB::transaction(function() use ($xchain_notification, $bot, &$should_update_bot_balance) {
            // load or create a new transaction from the database
            $transaction_model = $this->findOrCreateTransaction($xchain_notification['txid'], $bot['id']);
            if (!$transaction_model) { throw new Exception("Unable to access database", 1); }

            // determine the number of confirmations
            $confirmations = $xchain_notification['confirmations'];
            $is_confirmed = $xchain_notification['confirmed'];

            // assume the first source should get paid
            $destination = $xchain_notification['sources'][0];

            // load the swap receipts before updating any
            $swap_receipts = $transaction_model['swap_receipts'];

            // setup variables
            $should_process            = true;
            $any_processing_errors     = false;
            $should_update_transaction = false;
            $should_update_bot_balance = ($is_confirmed ? true : false);
            $any_notification_given    = false;


            // previously processed
            if ($should_process AND $transaction_model['processed']) {
                $this->swap_event_logger->logToBotEvents($bot, 'tx.previous', BotEvent::LEVEL_DEBUG, [
                    'msg'  => "Transaction {$xchain_notification['txid']} has already been processed.  Ignoring it.",
                    'txid' => $xchain_notification['txid']
                ]);
                $should_process = false;
                $any_notification_given = true;
            }


            // check for blacklisted sources (for confirmed transactions)
            if ($should_process AND !$transaction_model['processed']) {
                $blacklist_addresses = $bot['blacklist_addresses'];

                // never send to self
                $blacklist_addresses[] = $xchain_notification['notifiedAddress'];
                
                if (in_array($xchain_notification['sources'][0], $blacklist_addresses)) {
                    // blacklisted
                    $this->swap_event_logger->logSendFromBlacklistedAddress($bot, $xchain_notification, $is_confirmed);

                    $should_process = false;
                    $should_update_transaction = true;
                    $any_notification_given    = true;

                }
            }


            // process all relevant swaps for transactions that have not been processed yet
            if ($should_process AND !$transaction_model['processed']) {
                $any_swap_processed = false;

                foreach ($bot['swaps'] as $swap) {
                    if ($xchain_notification['asset'] == $swap['in']) {
                        try {
                            $any_swap_processed = true;

                            // build the swap strategy
                            $strategy = $this->strategy_factory->newStrategy($swap['strategy']);

                            // we recieved an asset - exchange 'in' for 'out'

                            // determine the swap ID
                            $swap_id = $bot->buildSwapID($swap);

                            // calculate the receipient's quantity and asset
                            list($quantity, $asset) = $strategy->buildSwapOutputQuantityAndAsset($swap, $xchain_notification);

                            // should we process this swap?
                            $should_process_swap = true;

                            // is this an unconfirmed tx?
                            if (!$is_confirmed) {
                                $should_process_swap = false;
                                $this->swap_event_logger->logUnconfirmedTx($bot, $xchain_notification, $destination, $quantity, $asset);
                                $any_notification_given = true;
                            }

                            // is the bot active?
                            if ($should_process_swap AND !$bot['active']) {
                                $should_process_swap = false;

                                // mark the transaction as processed
                                //   even though the bot was inactive
                                $should_update_transaction = true;

                                // log the inactive bot status
                                $this->swap_event_logger->logInactiveBot($bot, $xchain_notification);
                                $any_notification_given = true;
                            }


                            // see if the swap has already been handled
                            if ($should_process_swap AND isset($swap_receipts[$swap_id]) AND $swap_receipts[$swap_id]['txid']) {
                                $should_process_swap = false;

                                // this swap receipt already exists
                                $this->swap_event_logger->logPreviouslyProcessedSwap($bot, $xchain_notification, $destination, $quantity, $asset);
                                $any_notification_given = true;
                            }



                            // if all the checks above passed
                            //   then we should process this swap
                            if ($should_process_swap) {
                                // log the attempt to send
                                $this->swap_event_logger->logSendAttempt($bot, $xchain_notification, $destination, $quantity, $asset, $confirmations);

                                // send it
                                try {
                                    $send_result = $this->sendAssets($bot, $xchain_notification, $destination, $quantity, $asset, $swap_receipts);
                                } catch (Exception $e) {
                                    $any_processing_errors = true;
                                    throw $e;
                                }

                                // update the swap receipts in memory
                                $swap_receipts[$swap_id] = ['txid' => $send_result['txid'], 'confirmations' => $confirmations];

                                // mark any processed
                                $should_update_transaction = true;

                                $this->swap_event_logger->logSendResult($bot, $send_result, $xchain_notification, $destination, $quantity, $asset, $confirmations);
                                $any_notification_given = true;
                            }


                        } catch (Exception $e) {
                            // log any failure
                            if ($e instanceof SwapStrategyException) {
                                EventLog::logError('swap.failed', $e);
                                $this->swap_event_logger->logToBotEventsWithoutEventLog($bot, $e->getErrorName(), $e->getErrorLevel(), $e->getErrorData());
                            } else {
                                EventLog::logError('swap.failed', $e);
                                $this->swap_event_logger->logSwapFailed($bot, $xchain_notification, $e);
                            }
                            $any_notification_given = true;
                        }
                    }
                } // done processing swaps

                if (!$any_swap_processed) {
                    // we received an asset, but no swap was processed
                    $this->swap_event_logger->logUnknownReceiveTransaction($bot, $xchain_notification);
                    $any_notification_given = true;

                    // mark the transaction as processed
                    //   this is probably an attempt to fill up the bot
                    $should_update_transaction = true;
                }
            }

            // done going through swaps - update the swap receipts
            if ($should_update_transaction) {
                $update_vars = [
                    'swap_receipts' => $swap_receipts,
                    'confirmations' => $confirmations,
                ];

                // mark the transaction as processed only if there were no errros
                if (!$any_processing_errors) { $update_vars['processed'] = true; }

                $this->transaction_repository->update($transaction_model, $update_vars);
            }

            if (!$any_notification_given) {
                // no feedback was given to the user
                //   this should never happen
                $this->swap_event_logger->logUnhandledTransaction($bot, $xchain_notification);
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
    
    protected function sendAssets($bot, $xchain_notification, $destination, $quantity, $asset) {
        // call xchain
        $fee = $bot['return_fee'];
        $send_result = $this->xchain_client->send($bot['payment_address_id'], $destination, $quantity, $asset, $fee);

        return $send_result;
    }

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
