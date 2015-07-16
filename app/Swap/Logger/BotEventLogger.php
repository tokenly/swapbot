<?php

namespace Swapbot\Swap\Logger;

use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\CreateBotEvent;
use Swapbot\Events\BotEventCreated;
use Swapbot\Events\BotstreamEventCreated;
use Swapbot\Events\SwapEventCreated;
use Swapbot\Events\SwapstreamEventCreated;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;
use Swapbot\Models\BotLeaseEntry;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Swap;
use Swapbot\Models\Transaction;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\Logger\OutputTransformer\Facade\BotEventOutputTransformer;
use Tokenly\LaravelEventLog\Facade\EventLog;

class BotEventLogger {

    protected $EVENT_TEMPLATE_DATA = null;

    /**
     */
    public function __construct(BotEventRepository $bot_event_repository, BotRepository $bot_repository)
    {
        $this->bot_event_repository = $bot_event_repository;
        $this->bot_repository       = $bot_repository;
    }


    ////////////////////////////////////////////////////////////////////////
    // bot

    public function logBotStateChange(Bot $bot, $new_state) {
        return $this->logStandardBotEvent('bot.stateChange', $bot, ['state' => $new_state]);
    }

    public function logInactiveBotState(Bot $bot, $xchain_notification, BotState $bot_state) {
        $state_name = $bot_state->getName();

        switch ($state_name) {
            case BotState::BRAND_NEW:
                return $this->logLegacyBotEvent($bot, 'bot.brandnew', BotEvent::LEVEL_WARNING, [
                    'msg'   => "Ignored transaction {$xchain_notification['txid']} because this bot has not been paid for yet.",
                    'txid'  => $xchain_notification['txid'],
                    'state' => $state_name,
                ]);
                break;

            case BotState::LOW_FUEL:
                return $this->logLegacyBotEvent($bot, 'bot.lowfuel', BotEvent::LEVEL_WARNING, [
                    'msg'   => "Ignored transaction {$xchain_notification['txid']} because this bot is low on BTC fuel.",
                    'txid'  => $xchain_notification['txid'],
                    'state' => $state_name,
                ]);
                break;
            
            case BotState::INACTIVE:
                return $this->logLegacyBotEvent($bot, 'bot.inactive', BotEvent::LEVEL_WARNING, [
                    'msg'   => "Ignored transaction {$xchain_notification['txid']} because this bot is inactive.",
                    'txid'  => $xchain_notification['txid'],
                    'state' => $state_name,
                ]);

            case BotState::UNPAID:
                return $this->logLegacyBotEvent($bot, 'bot.unpaid', BotEvent::LEVEL_WARNING, [
                    'msg'   => "Ignored transaction {$xchain_notification['txid']} because this bot is unpaid.",
                    'txid'  => $xchain_notification['txid'],
                    'state' => $state_name,
                ]);

            default:
                return $this->logLegacyBotEvent($bot, 'bot.inactive', BotEvent::LEVEL_WARNING, [
                    'msg'   => "Ignored transaction {$xchain_notification['txid']} because this bot is in unknown state ({$state_name}).",
                    'txid'  => $xchain_notification['txid'],
                    'state' => $state_name,
                ]);
        }
    }

    public function logSendFromBlacklistedAddress(Bot $bot, $xchain_notification, $is_confirmed) {
        return $this->logLegacyBotEvent($bot, 'swap.ignored.blacklist', BotEvent::LEVEL_INFO, [
            'msg'         => "Ignored ".($is_confirmed?'':'unconfirmed ')."transaction of {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]} because sender address was blacklisted.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
        ]);
    }

    public function logPreviousTransaction(Bot $bot, $xchain_notification) {
        return $this->logXChainBotEvent('tx.previous', $bot, $xchain_notification);
    }

    public function logUnknownReceiveTransaction(Bot $bot, $xchain_notification) {
        $confirmations = $xchain_notification['confirmations'];
        $is_confirmed = ($confirmations > 0);
        $quantity = $xchain_notification['quantity'];
        $asset = $xchain_notification['asset'];

        return $this->logLegacyBotEvent($bot, 'receive.unknown', BotEvent::LEVEL_INFO, [
            'msg'           => "Received {$quantity} {$asset} with transaction ID {$xchain_notification['txid']}.  This transaction did not trigger any swaps.",
            'txid'          => $xchain_notification['txid'],
            'confirmations' => $confirmations,
            'source'        => $xchain_notification['sources'][0],
            'inQty'         => $quantity,
            'inAsset'       => $asset,
        ]);
    }

    // public function logUnhandledTransaction(Bot $bot, $xchain_notification) {
    //     return $this->logLegacyBotEvent($bot, 'tx.unhandled', BotEvent::LEVEL_WARNING, [
    //         'msg'           => "Transaction ID {$xchain_notification['txid']} was not handled by this swapbot.",
    //         'txid'          => $xchain_notification['txid'],
    //     ]);
    // }

    public function logBalanceUpdateFailed(Bot $bot, $e) {
        return $this->logLegacyBotEventWithoutEventLog($bot, 'balanceupdate.failed', BotEvent::LEVEL_WARNING, [
            'msg'   => "Failed to update balances.",
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
    }




    public function logUnconfirmedFuelTXReceived(Bot $bot, $xchain_notification) {
        return $this->logXChainBotEvent('payment.unconfirmedMoveFuel', $bot, $xchain_notification, [
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
        ]);
    }


    public function logFuelTXReceived(Bot $bot, $xchain_notification) {
        return $this->logXChainBotEvent('payment.moveFuelConfirmed', $bot, $xchain_notification, [
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
        ]);
    }

    // public function logFuelTXSent(Bot $bot, $xchain_notification) {
    //     $quantity = $xchain_notification['quantity'];
    //     $asset    = $xchain_notification['asset'];
    //     $tx_id    = $xchain_notification['txid'];
    //     return $this->logLegacyBotEvent($bot, 'payment.moveFuelSent', BotEvent::LEVEL_DEBUG, [
    //         'msg'           => "Sent swapbot fuel of {$quantity} {$asset} from the payment address with transaction ID {$tx_id}.",
    //         'qty'           => $quantity,
    //         'asset'         => $asset,
    //         'txid'          => $tx_id,
    //         'confirmations' => $xchain_notification['confirmations'],
    //     ]);

    // }





    ////////////////////////////////////////////////////////////////////////
    // payment

    public function logFirstMonthlyFeePaid(Bot $bot, $quantity, $asset) {
        return $this->logStandardBotEvent('payment.firstMonthlyFeePaid', $bot, ['qty' => $quantity, 'asset' => $asset,]);
    }

    public function logMonthlyFeePaid(Bot $bot, $quantity, $asset) {
        return $this->logStandardBotEvent('payment.monthlyFeePaid', $bot, ['qty' => $quantity, 'asset' => $asset,]);
    }

    public function logMonthlyFeePurchased(Bot $bot, $months, $cost, $asset) {
        return $this->logStandardBotEvent('payment.monthlyFeePurchased', $bot, [
            'months' => $months,
            'cost'   => $cost,
            'asset'  => $asset,
        ]);
    }

    public function logLeaseCreated(Bot $bot, BotLeaseEntry $lease_entry) {
        return $this->logStandardBotEvent('payment.leaseCreated', $bot, [
            'start_date' => (string)$lease_entry['start_date'],
            'end_date'   => (string)$lease_entry['end_date'],
        ]);
    }

    public function logUnconfirmedPaymentTx(Bot $bot, $xchain_notification) {
        return $this->logXChainBotEvent('payment.unconfirmed', $bot, $xchain_notification, [
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
        ]);
    }

    public function logConfirmedPaymentTx(Bot $bot, $xchain_notification) {
        return $this->logXChainBotEvent('payment.confirmed', $bot, $xchain_notification, [
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
        ]);
    }

    public function logPreviousPaymentTransaction(Bot $bot, $xchain_notification) {
        return $this->logXChainBotEvent('payment.previous', $bot, $xchain_notification);
    }

    public function logUnknownPaymentTransaction(Bot $bot, $xchain_notification) {
        return $this->logXChainBotEvent('payment.unknown', $bot, $xchain_notification, [
            'source'  => $xchain_notification['sources'][0],
            'inQty'   => $xchain_notification['quantity'],
            'inAsset' => $xchain_notification['asset'],
        ]);
    }


    public function logMoveInitialFuelTXCreated(Bot $bot, $quantity, $asset, $destination, $fee, $tx_id) {
        return $this->logStandardBotEvent('payment.moveFuelCreated', $bot, [
            'destination' => $destination,
            'outQty'      => $quantity,
            'outAsset'    => $asset,
            'fee'         => $fee,
            'txid'        => $tx_id,
        ]);
    }

    public function logMoveInitialFuelTXFailed(Bot $bot, Exception $e) {
        return $this->logLegacyBotEvent($bot, 'payment.moveFuelCreated.failed', BotEvent::LEVEL_WARNING, [
            'msg'   => "Failed to move initial swapbot fuel.",
            'error' => $e->getMessage(),
        ]);

    }

    public function logPaymentForwarded(Bot $bot, $quantity, $asset, $destination, $fee, $tx_id) {
        return $this->logStandardBotEvent('payment.forwarded', $bot, [
            'destination' => $destination,
            'outQty'      => $quantity,
            'outAsset'    => $asset,
            'fee'         => $fee,
            'txid'        => $tx_id,
        ]);
    }


    ////////////////////////////////////////////////////////////////////////
    // swap

    public function logNewSwap(Bot $bot, Swap $swap, $receipt_update_vars) {
        $this->logSwapEvent('swap.new', $bot, $swap, $receipt_update_vars);
    }

    public function logSwapStateChange(Swap $swap, $new_state, $swap_update_vars=null) {
        if ($swap_update_vars === null) { $swap_update_vars = []; }
        if (!isset($swap_update_vars['state'])) { $swap_update_vars['state'] = $new_state; }

        // load the bot
        $bot = $this->bot_repository->findByID($swap['bot_id']);

        $this->logSwapEvent('swap.stateChange', $bot, $swap, null, $swap_update_vars);
    }

    public function logSwapTransactionUpdate(Bot $bot, Swap $swap, $receipt_update_vars) {
        $this->logSwapEvent('swap.transaction.update', $bot, $swap, $receipt_update_vars);
    }

    public function logConfirmingSwap(Bot $bot, Swap $swap, $receipt_update_vars, $swap_update_vars=null) {
        $this->logSwapEvent('swap.confirming', $bot, $swap, $receipt_update_vars, $swap_update_vars);
    }

    public function logConfirmedSwap(Bot $bot, Swap $swap, $receipt_update_vars, $swap_update_vars=null) {
        $this->logSwapEvent('swap.confirmed', $bot, $swap, $receipt_update_vars, $swap_update_vars);
    }

    public function logSwapSent(Bot $bot, Swap $swap, $receipt_update_vars, $swap_update_vars=null) {
        $this->logSwapEvent('swap.sent', $bot, $swap, $receipt_update_vars, $swap_update_vars);
        // Received {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]} with {$confirmations} confirmation".($confirmations==1?'':'s').". Sent {$quantity} {$asset} to {$destination} with transaction ID {$send_result['txid']}.
    }

    public function logRefundAttempt(Bot $bot, Swap $swap, $receipt_update_vars, $swap_update_vars=null) {
        $this->logSwapEvent('swap.refunding', $bot, $swap, $receipt_update_vars, $swap_update_vars);
    }
    
    public function logSwapRefunded(Bot $bot, Swap $swap, $receipt_update_vars, $swap_update_vars=null) {
        // log the send
        $this->logSwapEvent('swap.refunded', $bot, $swap, $receipt_update_vars, $swap_update_vars);
    }    

    public function logSwapSendConfirmed(Bot $bot, Swap $swap, $receipt_update_vars, $swap_update_vars=null) {
        $this->logSwapEvent('send.confirmed', $bot, $swap, $receipt_update_vars, $swap_update_vars);
    }

    public function logUnconfirmedSwapSend(Bot $bot, Swap $swap, $receipt_update_vars) {
        $this->logSwapEvent('send.unconfirmed', $bot, $swap, $receipt_update_vars);
    }


    public function logSwapNotReady(Bot $bot, Swap $swap) {
        $this->logSwapEvent('swap.notReady', $bot, $swap);
    }



    public function logSwapFailed(Bot $bot, Swap $swap, Exception $e, $receipt_update_vars) {
        $this->logSwapEvent('swap.failed', $bot, $swap, $receipt_update_vars, null, ['error' => $e->getMessage()]);
    }

    public function logSwapRetry(Bot $bot, Swap $swap) {
        $swap_name = $swap['name'];
        return $this->logLegacyBotEventWithoutEventLog($bot, 'swap.retry', BotEvent::LEVEL_DEBUG, [
            'msg'    => "Retrying previously errored swap {$swap_name}.",
            'swapId' => $swap['uuid'],
        ]);
    }






    public function logSendAttempt(Bot $bot, Swap $swap, $xchain_notification, $destination, $quantity, $asset, $confirmations) {
        // log the send
        return $this->logLegacyBotEvent($bot, 'swap.found', BotEvent::LEVEL_DEBUG, [
            'msg'           => "Received {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]} with {$confirmations} confirmation".($confirmations==1?'':'s').". Will vend {$quantity} {$asset} to {$destination}.",
            'txid'          => $xchain_notification['txid'],
            'source'        => $xchain_notification['sources'][0],
            'inQty'         => $xchain_notification['quantity'],
            'inAsset'       => $xchain_notification['asset'],
            'destination'   => $destination,
            'outQty'        => $quantity,
            'outAsset'      => $asset,
            'confirmations' => $confirmations,
            'swapId'        => $swap['uuid'],
        ]);
    }
    

    public function logPreviouslyProcessedSwap(Bot $bot, $xchain_notification, $destination, $quantity, $asset) {
        return $this->logLegacyBotEvent($bot, 'swap.processed.previous', BotEvent::LEVEL_DEBUG, [
            'msg'         => "Received a transaction of {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]}.  Did not vend {$asset} to {$destination} because this swap has already been sent.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
            'destination' => $destination,
            'outQty'      => $quantity,
            'outAsset'    => $asset,
        ]);
    }



    ////////////////////////////////////////////////////////////////////////
    // payments

    public function logManualPayment(Bot $bot, $amount, $asset, $is_credit=true, $msg=null) {
        if ($msg === null) {
            if ($is_credit) {
                $msg = "Applied a credit of {$amount} {$asset}.";
            } else {
                $msg = "Applied a debit of {$amount} {$asset}.";
            }
        } else {
            $msg = str_replace('{{amount}}', $amount, $msg);
            $msg = str_replace('{{asset}}', $asset, $msg);
        }

        return $this->logLegacyBotEvent($bot, 'payment.manual', BotEvent::LEVEL_INFO, [
            'msg'       => $msg,
            'amount'    => $amount,
            'is_credit' => $is_credit,
        ]);
    }

    public function logTransactionFee(Bot $bot, $fee, $transaction_id) {
        return $this->logLegacyBotEvent($bot, 'fee.transaction', BotEvent::LEVEL_INFO, [
            'msg'           => "Paid a transaction fee of $fee.",
            'fee'           => $fee,
            'transactionId' => $transaction_id,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////
    // sends


    public function logUnknownSendTransaction(Bot $bot, $xchain_notification) {
        $confirmations = $xchain_notification['confirmations'];
        $quantity      = $xchain_notification['quantity'];
        $asset         = $xchain_notification['asset'];
        $destination   = $xchain_notification['destinations'][0];

        return $this->logLegacyBotEvent($bot, 'send.unknown', BotEvent::LEVEL_WARNING, [
            'msg'           => "Sent {$quantity} {$asset} to {$destination} with transaction ID {$xchain_notification['txid']}.  This transaction did not match any swaps.",
            'txid'          => $xchain_notification['txid'],
            'destination'   => $destination,
            'outQty'        => $quantity,
            'outAsset'      => $asset,
            'confirmations' => $confirmations,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////
    // income forwarding

    
    public function logIncomeForwardingResult(Bot $bot, $send_result, $destination, $quantity, $asset) {
        // log the send
        return $this->logStandardBotEvent('income.forwarded', $bot, [
            'quantityOut'   => $quantity,
            'assetOut'      => $asset,
            'txid'          => $send_result['txid'],
            'destination'   => $destination,
        ]);

    }

    public function logIncomeForwardingFailed(Bot $bot, $e) {
        return $this->logLegacyBotEventWithoutEventLog($bot, 'income.forward.failed', BotEvent::LEVEL_WARNING, [
            'msg'   => "Failed to forward income.",
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
    }

    public function logIncomeForwardingTxSent(Bot $bot, $xchain_notification) {
        $quantity    = $xchain_notification['quantity'];
        $asset       = $xchain_notification['asset'];
        $tx_id       = $xchain_notification['txid'];
        $destination = $xchain_notification['destinations'][0];

        return $this->logStandardBotEvent('income.forwardSent', $bot, [
            'quantityOut'   => $quantity,
            'assetOut'      => $asset,
            'txid'          => $tx_id,
            'destination'   => $destination,
            'confirmations' => $xchain_notification['confirmations'],
        ]);
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Log Swap Event

    public function logSwapEvent($event_name, Bot $bot, Swap $swap, $receipt_update_vars=null, $swap_update_vars=null, $extra_event_vars=null, $write_to_application_log=true) {
        $event_template_data = $this->getEventTemplate($event_name);

        $swap_details_for_event_log = $this->buildSwapDetailsForLog($bot, $swap, $event_template_data, $receipt_update_vars, $swap_update_vars);

        // merge $extra_event_vars
        if ($extra_event_vars !== null) { $swap_details_for_event_log = array_merge($swap_details_for_event_log, $extra_event_vars); }

        // log the bot event
        if ($write_to_application_log) { EventLog::log($event_name, $swap_details_for_event_log); }

        // save the bot event
        $bot_event_model = $this->saveBotEventToRepository($event_name, $bot, $swap, $swap_details_for_event_log);
        $serialized_bot_event_model = $bot_event_model->serializeForAPI();

        if ($event_template_data['swapEventStream'] ) {
            // publish to event stream
            Event::fire(new SwapstreamEventCreated($swap, $bot, $serialized_bot_event_model));
        }

        // fire swap event
        Event::fire(new SwapEventCreated($swap, $bot, $serialized_bot_event_model));
    }

    protected function buildSwapDetailsForLog($bot, $swap, $event_template_data, $receipt_update_vars=null, $swap_update_vars=null) {
        $receipt = (array)$swap['receipt'];
        if ($receipt_update_vars !== null) { $receipt = array_merge($receipt, $receipt_update_vars); }

        // get the state
        $state = ($swap_update_vars !== null AND isset($swap_update_vars['state'])) ? $swap_update_vars['state'] : $swap['state'];

        $swap_details_for_log = [
            'destination'      => isset($receipt['destination'])      ? $receipt['destination']       : null,

            'quantityIn'       => isset($receipt['quantityIn'])       ? $receipt['quantityIn']        : null,
            'assetIn'          => isset($receipt['assetIn'])          ? $receipt['assetIn']           : null,
            'txidIn'           => isset($receipt['txidIn'])           ? $receipt['txidIn']            : null,
            'confirmations'    => isset($receipt['confirmations'])    ? $receipt['confirmations']     : null,

            'quantityOut'      => isset($receipt['quantityOut'])      ? $receipt['quantityOut']       : null,
            'assetOut'         => isset($receipt['assetOut'])         ? $receipt['assetOut']          : null,
            'txidOut'          => isset($receipt['txidOut'])          ? $receipt['txidOut']           : null,
            'confirmationsOut' => isset($receipt['confirmationsOut']) ? $receipt['confirmationsOut']  : null,

            'changeOut'        => isset($receipt['changeOut'])        ? $receipt['changeOut']         : null,
            'changeOutAsset'   => isset($receipt['changeOut'])        ? 'BTC'                         : null,

            'completedAt'      => isset($receipt['completedAt'])      ? $receipt['completedAt']       : null,

            'state'            => $state,
            'isComplete'       => $swap->isComplete($state),
            'isError'          => $swap->isError($state),
        ];

        // determine event vars
        if (isset($event_template_data['eventVars'])) {
            $all_event_vars = false;
            $event_vars_map = array_fill_keys($event_template_data['eventVars'], true);

        } else {
            $all_event_vars = true;
            $event_vars_map = [];
        }

        // filter null values and ignore keys not specified in eventVars
        $filtered_swap_details = $swap_details_for_log;
        foreach(array_keys($swap_details_for_log) as $key) {
            // filter if not a specified event var
            if (!$all_event_vars AND !isset($event_vars_map[$key])) {
                unset($filtered_swap_details[$key]);
                continue;
            }

            // filter if null
            if ($filtered_swap_details[$key] === null) {
                unset($filtered_swap_details[$key]);
                continue;
            }
        }
        $swap_details_for_log = $filtered_swap_details;

        return $swap_details_for_log;
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Log Bot Event


    protected function logStandardBotEvent($event_name, Bot $bot, $event_vars=null, $write_to_application_log=true) {
        $bot_details_for_event_log = $this->buildStandardBotEventDetailsForLog($bot, $event_vars);
        return $this->logBotEvent($event_name, $bot, $bot_details_for_event_log, $write_to_application_log);
    }

    protected function logXChainBotEvent($event_name, Bot $bot, $xchain_notification, $event_vars=null, $write_to_application_log=true) {
        $bot_details_for_event_log = $this->buildXChainBotEventDetailsForLog($bot, $xchain_notification, $event_vars);
        return $this->logBotEvent($event_name, $bot, $bot_details_for_event_log, $write_to_application_log);
    }

    protected function buildStandardBotEventDetailsForLog($bot, $event_vars=null) {
        // get the state
        $state = ($event_vars !== null AND isset($event_vars['state'])) ? $event_vars['state'] : $bot['state'];

        $event_details_for_log = [
            'state'    => $bot['state'],
            'isActive' => $bot->isActive($state),
        ];

        // merge the bot update vars
        if ($event_vars !== null) { $event_details_for_log = array_merge($event_vars, $event_details_for_log); }

        return $event_details_for_log;
    }

    protected function buildXChainBotEventDetailsForLog($bot, $xchain_notification, $event_vars=null) {
        // get the state
        $state = ($event_vars !== null AND isset($event_vars['state'])) ? $event_vars['state'] : $bot['state'];

        $event_details_for_log = [
            'txid'          => $xchain_notification['txid'],
            'confirmations' => $xchain_notification['confirmations'],

            'state'         => $state,
            'isActive'      => $bot->isActive($state),
        ];

        // merge the bot update vars
        if ($event_vars !== null) { $event_details_for_log = array_merge($event_vars, $event_details_for_log); }

        // filter null values
        $filtered_event_details = $event_details_for_log;
        foreach(array_keys($event_details_for_log) as $key) { if ($filtered_event_details[$key] === null) { unset($filtered_event_details[$key]); }; }
        $event_details_for_log = $filtered_event_details;

        return $event_details_for_log;
    }





    protected function logBotEvent($event_name, Bot $bot, $event_vars=null, $write_to_application_log=true) {
        $event_template_data = $this->getEventTemplate($event_name);

        // save the bot event
        $bot_event_model = $this->saveBotEventToRepository($event_name, $bot, null, $event_vars);

        // log the bot event
        if ($write_to_application_log) { EventLog::log($event_name, array_merge(['msg' => BotEventOutputTransformer::buildMessage($bot_event_model)], $event_vars)); }


        $serialized_bot_event_model = $bot_event_model->serializeForAPI();
        if (isset($event_template_data['botEventStream']) AND $event_template_data['botEventStream']) {
            // publish to event stream
            Event::fire(new BotstreamEventCreated($bot, $serialized_bot_event_model));
        }

        // fire a bot event
        Event::fire(new BotEventCreated($bot, $serialized_bot_event_model));

        return $bot_event_model;
    }

    protected function saveBotEventToRepository($event_name, $bot, $swap, $event_vars) {
        $event_template_data = $this->getEventTemplate($event_name);
        $level = constant('Swapbot\Models\BotEvent::LEVEL_'.strtoupper($event_template_data['level']));

        $event_data = ['name' => $event_name];
        $event_data = array_merge($event_data, $event_vars);

        $create_vars = [
            'bot_id'      => $bot['id'],
            'swap_id'     => $swap ? $swap['id'] : null,
            'level'       => $level,
            'event'       => $event_data,
            'swap_stream' => isset($event_template_data['swapEventStream']) ? $event_template_data['swapEventStream'] : false,
            'bot_stream'  => isset($event_template_data['botEventStream']) ? $event_template_data['botEventStream'] : false,
        ];

        // create the bot event
        $bot_event_model = $this->bot_event_repository->create($create_vars);

        return $bot_event_model;
    }

    protected function getEventTemplate($event_name) {
        if (!isset($this->EVENT_TEMPLATE_DATA)) {
            $this->EVENT_TEMPLATE_DATA = include(realpath(base_path('resources/data/events/compiled')).'/allEvents.data.php');
        }

        if (!isset($this->EVENT_TEMPLATE_DATA[$event_name])) { throw new Exception("Event template not found for {$event_name}", 1); }

        return $this->EVENT_TEMPLATE_DATA[$event_name];
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Legacy Bot Events (DEPRECATED)

    public function logLegacyBotEvent(Bot $bot, $event_name, $level, $event_data, $write_to_application_log = true) {
        return $this->createLegacyBotEvent($bot, null, $event_name, $level, $event_data, $write_to_application_log);
    }

    public function logLegacyBotEventWithoutEventLog(Bot $bot, $event_name, $level, $event_data) {
        return $this->logLegacyBotEvent($bot, $event_name, $level, $event_data, false);
    }


    public function createLegacyBotEvent($bot, $swap, $event_name, $level, $event_data, $write_to_application_log=true) {
        // log the bot event
        if ($write_to_application_log) { EventLog::log($event_name, $event_data); }

        $event_data['name'] = $event_name;

        $create_vars = [
            'bot_id'  => $bot['id'],
            'swap_id' => $swap ? $swap['id'] : null,
            'level'   => $level,
            'event'   => $event_data,
        ];

        // create the bot event
        $bot_event_model = $this->bot_event_repository->create($create_vars);

        // fire a bot event
        Event::fire(new BotEventCreated($bot, $bot_event_model->serializeForAPI()));

        if ($swap) {
            // also fire a swap event if this is a swap event
            Event::fire(new SwapEventCreated($swap, $bot, $bot_event_model->serializeForAPI()));
        }

        return $bot_event_model;
    }
    
    

}
