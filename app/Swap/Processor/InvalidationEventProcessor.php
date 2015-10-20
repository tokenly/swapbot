<?php

namespace Swapbot\Swap\Processor;

use ArrayObject;
use Exception;
use Illuminate\Database\Eloquent\toArray;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ProcessIncomeForwardingForAllBots;
use Swapbot\Models\BotEvent;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Data\SwapStateEvent;
use Swapbot\Providers\Accounts\Facade\AccountHandler;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Repositories\TransactionRepository;
use Swapbot\Swap\Logger\Facade\BotEventLogger;
use Swapbot\Swap\Processor\Util\BalanceUpdater;
use Tokenly\LaravelEventLog\Facade\EventLog;

class InvalidationEventProcessor {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, SwapRepository $swap_repository, TransactionRepository $transaction_repository, BalanceUpdater $balance_updater)
    {
        $this->bot_repository         = $bot_repository;
        $this->swap_repository        = $swap_repository;
        $this->transaction_repository = $transaction_repository;
        $this->balance_updater        = $balance_updater;
    }


    public function handleInvalidation($xchain_notification) {
        // receive, send, payment_receive, payment_send
        list($invalidation_type, $bot) = $this->resolveInvalidationTypeAndBot($xchain_notification);
        if ($invalidation_type === null) {
            EventLog::logError('invalidation.notFound', ['reason' => 'no bot found', 'notifiedAddressId' => $xchain_notification['notifiedAddressId'], 'notificationId' => $xchain_notification['notificationId']]);
            throw new Exception("Unable to find bot with monitor ID {$xchain_notification['notifiedAddressId']}.  notificationId was {$xchain_notification['notificationId']}", 1);
        }

        switch ($invalidation_type) {
            case 'receive':
                // change the incoming transaction ID or invalidate the swap
                $this->handleReceiveInvalidation($bot, $xchain_notification);
                break;

            case 'send':
                // change the outgoing transaction ID
                $this->handleSendInvalidation($bot, $xchain_notification);
                break;
            
            default:
                EventLog::logError('invalidation.unhandled', ['reason' => 'Not implemented', 'invalidation_type' => $invalidation_type]);
                break;
        }


    }

    // ------------------------------------------------------------------------
    
    protected function resolveInvalidationTypeAndBot($xchain_notification) {
        $invalidation_type = null;
        $bot = null;

        if ($invalidation_type === null) {
            // find a bot for this notification if it is received on a public address
            $bot = $this->bot_repository->findByPublicMonitorID($xchain_notification['notifiedAddressId']);
            if ($bot) { $invalidation_type = 'receive'; }
        }

        if ($invalidation_type === null) {
            // check if this is a send from the the public address
            $bot = $this->bot_repository->findBySendMonitorID($xchain_notification['notifiedAddressId']);
            if ($bot) { $invalidation_type = 'send'; }
        }

        // find a bot for this notification if it is received on the payment address
        if ($invalidation_type === null) {
            $bot = $this->bot_repository->findByPaymentMonitorID($xchain_notification['notifiedAddressId']);
            if ($bot) { $invalidation_type = 'payment_receive'; }
        }

        if ($invalidation_type === null) {
            // if this is a send from the payment address 
            //   this could be an initial fuel transaction, or an income forwarding transaction
            $bot = $this->bot_repository->findByPaymentSendMonitorID($xchain_notification['notifiedAddressId']);
            if ($bot) { $invalidation_type = 'payment_send'; }
        }

        return [$invalidation_type, $bot];
    }


    protected function handleReceiveInvalidation($bot, $xchain_notification) {
        $this->bot_repository->executeWithLockedBot($bot, function($bot) use ($xchain_notification) {
            $invalid_txid = $xchain_notification['invalidTxid'];
            $replacing_txid = $xchain_notification['replacingTxid'];

            // original swap
            $original_swap = $this->findSwapByTXID($invalid_txid, $bot);
            if (!$original_swap) {
                EventLog::logError('invalidation.receive.notFound', ['reason' => "no swaps found for $invalid_txid"]);
                throw new Exception("Unable to find transaction $invalid_txid", 1);
            }

            $is_the_same_transaction = ($xchain_notification['replacingNotification']['transactionFingerprint'] == $xchain_notification['invalidNotification']['transactionFingerprint']);
            // Log::debug("\$is_the_same_transaction=".json_encode($is_the_same_transaction, 192));

            // the new swap already exists
            $confirmed_swap = $this->findSwapByTXID($replacing_txid, $bot);
            if ($confirmed_swap) {
                // send an event that this new swap replaced the old swap
                if ($is_the_same_transaction) {
                    // perhaps migrate customers here...

                    // log the replace
                    BotEventLogger::logSwapReplaced($bot, $original_swap, $confirmed_swap);
                }

                $this->invalidateAndCloseSwap($original_swap);

            } else {
                // a new swap with this txid doesn't exist yet
                if ($is_the_same_transaction) {
                    // just change the txidIn of the original swap
                    $swap_update_vars = [
                        'receipt' => ['txidIn' => $replacing_txid,],
                    ];
                    $update_vars = $this->swap_repository->mergeUpdateVars($original_swap, $swap_update_vars);
                    $this->swap_repository->update($original_swap, $update_vars);

                    // log event
                    BotEventLogger::logSwapTXIDInUpdate($bot, $original_swap, $swap_update_vars['receipt'], $invalid_txid);
                } else {
                    // the original swap will never confirm
                    $this->invalidateAndCloseSwap($original_swap);
                }
            }
        });

        // sync the bot balances
        $this->balance_updater->syncBalances($bot);
    }

    // ------------------------------------------------------------------------
    
    protected function handleSendInvalidation($bot, $xchain_notification) {
        $this->bot_repository->executeWithLockedBot($bot, function($bot) use ($xchain_notification) {
            $invalid_txid = $xchain_notification['invalidTxid'];
            $replacing_txid = $xchain_notification['replacingTxid'];

            // original swap
            $original_swap = $this->findSendingSwapByTXIDOut($invalid_txid, $bot);
            if (!$original_swap) {
                EventLog::log('invalidation.send.notFound', ['reason' => "no sending transactions found for $invalid_txid"]);
                return;
            }

            $is_the_same_transaction = ($xchain_notification['replacingNotification']['transactionFingerprint'] == $xchain_notification['invalidNotification']['transactionFingerprint']);

            // the new swap already exists
            $confirmed_swap = $this->findSendingSwapByTXIDOut($replacing_txid, $bot);
            if ($confirmed_swap) {
                EventLog::logError('invalidation.send.alreadyFound', [
                    'reason'        => "A sending swap was already found for new txid $replacing_txid",
                    'replacingTxid' => $replacing_txid,
                    'swapUUID'      => $confirmed_swap['uuid'],
                ]);
                return;
            } else {
                // a new swap with this txid doesn't exist yet
                if ($is_the_same_transaction) {
                    // just change the txidOut of the original swap
                    $swap_update_vars = [
                        'receipt' => ['txidOut' => $replacing_txid,],
                    ];
                    $update_vars = $this->swap_repository->mergeUpdateVars($original_swap, $swap_update_vars);
                    $this->swap_repository->update($original_swap, $update_vars);

                    // log event
                    BotEventLogger::logSwapTXIDOutUpdate($bot, $original_swap, $swap_update_vars['receipt'], $invalid_txid);
                } else {
                    EventLog::logError('invalidation.send.differentTransaction', [
                        'reason'        => "The new sent txid fingerprint did not match the original",
                        'invalidTxid'   => $invalid_txid,
                        'replacingTxid' => $replacing_txid,
                    ]);
                    return;
                }
            }
        });

        // sync the bot balances
        $this->balance_updater->syncBalances($bot);
    }

    protected function findSwapByTXID($txid, $bot) {
        // get the transaction
        $transaction = $this->transaction_repository->findByTransactionIDAndBotID($txid, $bot['id']);
        if (!$transaction) { return null; }

        // find the swap with this transaction as a receive
        $swaps = $this->swap_repository->findByTransactionID($transaction['id']);
        if (count($swaps) > 1) {
            EventLog::logError('invalidation.multipleReceives', ['reason' => "multiple swaps found for txid $txid"]);
            throw new Exception("multiple receiving swaps found for txid $txid", 1);
        }

        return $swaps->first();
    }

    protected function findSendingSwapByTXIDOut($txid, $bot) {
        $swaps = $this->swap_repository->findByBotIDWithStates($bot['id'], [SwapState::SENT, SwapState::REFUNDED]);

        $matched_swaps = [];
        foreach($swaps as $swap) {
            if (isset($swap['receipt']['txidOut']) AND $swap['receipt']['txidOut'] == $txid) {
                $matched_swaps[] = $swap;
            }
        }

        if (count($matched_swaps) > 1) {
            EventLog::logError('invalidation.multipleSends', ['reason' => "multiple sending swaps found for txid $txid"]);
            throw new Exception("multiple sending swaps found for txid $txid", 1);
        }

        if (!$matched_swaps) { return null; }
        return $matched_swaps[0];
    }

    protected function invalidateAndCloseSwap($swap) {
        // move the swap to an invalidated state
        $swap->stateMachine()->triggerEvent(SwapStateEvent::SWAP_WAS_INVALIDATED);

        // close the swap account with xchain
        AccountHandler::closeSwapAccount($swap);

    }
}
