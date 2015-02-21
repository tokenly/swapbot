<?php

namespace Swapbot\Swap\Logger;

use Exception;
use Illuminate\Support\Facades\Event;
use Swapbot\Commands\CreateBotEvent;
use Swapbot\Events\BotEventCreated;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\BotEventRepository;
use Tokenly\LaravelEventLog\Facade\EventLog;

class BotEventLogger {

    /**
     */
    public function __construct(BotEventRepository $bot_event_repository)
    {
        $this->bot_event_repository = $bot_event_repository;
    }

    public function logSendAttempt(Bot $bot, $xchain_notification, $destination, $quantity, $asset, $confirmations) {
        // log the send
        return $this->logToBotEvents($bot, 'swap.found', BotEvent::LEVEL_DEBUG, [
            'msg'         => "Received {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]} with {$confirmations} confirmation".($confirmations==1?'':'s').". Will vend {$quantity} {$asset} to {$destination}.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
            'destination' => $destination,
            'outQty'      => $quantity,
            'outAsset'    => $asset,
        ]);
    }
    
    public function logSendResult(Bot $bot, $send_result, $xchain_notification, $destination, $quantity, $asset, $confirmations) {
        // log the send
        return $this->logToBotEvents($bot, 'swap.sent', BotEvent::LEVEL_INFO, [
            // Received 500 LTBCOIN from SENDER01 with 1 confirmation.  Sent 0.0005 BTC to SENDER01 with transaction ID 0000000000000000000000000000001111
            'msg'         => "Received {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]} with {$confirmations} confirmation".($confirmations==1?'':'s').". Sent {$quantity} {$asset} to {$destination} with transaction ID {$send_result['txid']}.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
            'destination' => $destination,
            'outQty'      => $quantity,
            'outAsset'    => $asset,
            'sentTxID'    => $send_result['txid'],
        ]);

    }

    public function logUnconfirmedTx(Bot $bot, $xchain_notification, $destination, $quantity, $asset) {
        return $this->logToBotEvents($bot, 'unconfirmed.tx', BotEvent::LEVEL_INFO, [
            'msg'         => "Received an unconfirmed transaction of {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]}.  Will vend {$quantity} {$asset} to {$destination} when it confirms.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
            'destination' => $destination,
            'outQty'      => $quantity,
            'outAsset'    => $asset,
        ]);
    }

    public function logUnconfirmedPaymentTx(Bot $bot, $xchain_notification) {
        return $this->logToBotEvents($bot, 'payment.unconfirmed', BotEvent::LEVEL_INFO, [
            'msg'         => "Received an unconfirmed payment of {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]}.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
        ]);
    }

    public function logConfirmedPaymentTx(Bot $bot, $xchain_notification) {
        return $this->logToBotEvents($bot, 'payment.confirmed', BotEvent::LEVEL_INFO, [
            'msg'           => "Received a confirmed payment of {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]}.",
            'txid'          => $xchain_notification['txid'],
            'source'        => $xchain_notification['sources'][0],
            'inQty'         => $xchain_notification['quantity'],
            'inAsset'       => $xchain_notification['asset'],
            'confirmations' => $xchain_notification['confirmations'],
        ]);
    }

    public function logUnknownPaymentTransaction(Bot $bot, $xchain_notification) {
        $confirmations = $xchain_notification['confirmations'];
        $quantity = $xchain_notification['quantity'];
        $asset = $xchain_notification['asset'];

        return $this->logToBotEvents($bot, 'payment.unknown', BotEvent::LEVEL_WARNING, [
            'msg'           => "Received a payment of {$quantity} {$asset} with transaction ID {$xchain_notification['txid']}. This was not a valid payment.",
            'txid'          => $xchain_notification['txid'],
            'confirmations' => $confirmations,
            'source'        => $xchain_notification['sources'][0],
            'inQty'         => $quantity,
            'inAsset'       => $asset,
        ]);
    }

    public function logInactiveBotState(Bot $bot, $xchain_notification, BotState $bot_state) {
        $state_name = $bot_state->getName();

        switch ($state_name) {
            case BotState::BRAND_NEW:
                return $this->logToBotEvents($bot, 'bot.brandnew', BotEvent::LEVEL_WARNING, [
                    'msg'   => "Ignored transaction {$xchain_notification['txid']} because this bot has not been paid for yet.",
                    'txid'  => $xchain_notification['txid'],
                    'state' => $state_name,
                ]);
                break;

            case BotState::LOW_FUEL:
                return $this->logToBotEvents($bot, 'bot.lowfuel', BotEvent::LEVEL_WARNING, [
                    'msg'   => "Ignored transaction {$xchain_notification['txid']} because this bot is low on BTC fuel.",
                    'txid'  => $xchain_notification['txid'],
                    'state' => $state_name,
                ]);
                break;
            
            case BotState::INACTIVE:
                return $this->logToBotEvents($bot, 'bot.inactive', BotEvent::LEVEL_WARNING, [
                    'msg'   => "Ignored transaction {$xchain_notification['txid']} because this bot is inactive.",
                    'txid'  => $xchain_notification['txid'],
                    'state' => $state_name,
                ]);

            default:
                return $this->logToBotEvents($bot, 'bot.inactive', BotEvent::LEVEL_WARNING, [
                    'msg'   => "Ignored transaction {$xchain_notification['txid']} because this bot is in unknown state ({$state_name}).",
                    'txid'  => $xchain_notification['txid'],
                    'state' => $state_name,
                ]);
        }
    }

    public function logPreviouslyProcessedSwap(Bot $bot, $xchain_notification, $destination, $quantity, $asset) {
        return $this->logToBotEvents($bot, 'swap.processed.previous', BotEvent::LEVEL_DEBUG, [
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

    public function logSendFromBlacklistedAddress(Bot $bot, $xchain_notification, $is_confirmed) {
        return $this->logToBotEvents($bot, 'swap.ignored.blacklist', BotEvent::LEVEL_INFO, [
            'msg'         => "Ignored ".($is_confirmed?'':'unconfirmed ')."transaction of {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]} because sender address was blacklisted.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
        ]);
    }

    public function logUnconfirmedSendTx(Bot $bot, $xchain_notification, $destination, $quantity, $asset) {
        return $this->logToBotEvents($bot, 'send.unconfirmed', BotEvent::LEVEL_DEBUG, [
            'msg'         => "Saw unconfirmed send of {$quantity} {$asset} to {$destination} with transaction ID {$xchain_notification['txid']}.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'outQty'      => $quantity,
            'outAsset'    => $asset,
            'destination' => $destination,
        ]);
    }

    public function logConfirmedSendTx(Bot $bot, $xchain_notification, $destination, $quantity, $asset, $confirmations) {
        return $this->logToBotEvents($bot, 'send.confirmed', BotEvent::LEVEL_INFO, [
            'msg'           => "Saw confirmed send of {$quantity} {$asset} to {$destination} with transaction ID {$xchain_notification['txid']}.",
            'txid'          => $xchain_notification['txid'],
            'confirmations' => $confirmations,
            'source'        => $xchain_notification['sources'][0],
            'outQty'        => $quantity,
            'outAsset'      => $asset,
            'destination'   => $destination,
        ]);
    }

    public function logUnknownReceiveTransaction(Bot $bot, $xchain_notification) {
        $confirmations = $xchain_notification['confirmations'];
        $is_confirmed = ($confirmations > 0);
        $quantity = $xchain_notification['quantity'];
        $asset = $xchain_notification['asset'];

        return $this->logToBotEvents($bot, 'receive.unknown', BotEvent::LEVEL_INFO, [
            'msg'           => "Received {$quantity} {$asset} with transaction ID {$xchain_notification['txid']}.  This transaction did not trigger any swaps.",
            'txid'          => $xchain_notification['txid'],
            'confirmations' => $confirmations,
            'source'        => $xchain_notification['sources'][0],
            'inQty'         => $quantity,
            'inAsset'       => $asset,
        ]);
    }

    public function logUnhandledTransaction(Bot $bot, $xchain_notification) {
        return $this->logToBotEvents($bot, 'tx.unhandled', BotEvent::LEVEL_WARNING, [
            'msg'           => "Transaction ID {$xchain_notification['txid']} was not handled by this swapbot.",
            'txid'          => $xchain_notification['txid'],
        ]);
    }

    public function logSwapFailed(Bot $bot, $xchain_notification, $e) {
        return $this->logToBotEventsWithoutEventLog($bot, 'swap.failed', BotEvent::LEVEL_WARNING, [
            'msg'   => "Failed to swap asset.",
            'error' => $e->getMessage(),
            'txid'  => $xchain_notification['txid'],
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
    }

    public function logBalanceUpdateFailed(Bot $bot, $e) {
        return $this->logToBotEventsWithoutEventLog($bot, 'balanceupdate.failed', BotEvent::LEVEL_WARNING, [
            'msg'   => "Failed to update balances.",
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
    }



    public function logMoveInitialFuelTXCreated(Bot $bot, $quantity, $asset, $destination, $fee, $tx_id) {
        return $this->logToBotEvents($bot, 'payment.moveFuelCreated', BotEvent::LEVEL_DEBUG, [
            'msg'         => "Moving initial swapbot fuel.  Sent {$quantity} {$asset} to {$destination} with transaction ID {$tx_id}.",
            'destination' => $destination,
            'outQty'      => $quantity,
            'outAsset'    => $asset,
            'fee'         => $fee,
            'sentTxID'    => $tx_id,
        ]);

    }

    public function logFuelTXReceived(Bot $bot, $xchain_notification) {
        $quantity = $xchain_notification['quantity'];
        $asset    = $xchain_notification['asset'];
        $tx_id    = $xchain_notification['txid'];
        return $this->logToBotEvents($bot, 'payment.moveFuelConfirmed', BotEvent::LEVEL_INFO, [
            'msg'           => "Received swapbot fuel of {$quantity} {$asset} from the payment address with transaction ID {$tx_id}.",
            'qty'           => $quantity,
            'asset'         => $asset,
            'txid'          => $tx_id,
            'confirmations' => $xchain_notification['confirmations'],
        ]);

    }


    public function logInitialCreationFeePaid(Bot $bot, $quantity, $asset) {
        return $this->logToBotEvents($bot, 'payment.creationFeePaid', BotEvent::LEVEL_INFO, [
            'msg'   => "Paid {$quantity} {$asset} as a creation fee.",
            'qty'   => $quantity,
            'asset' => $asset,
        ]);

    }

    public function logBotStateChange(Bot $bot, $new_state) {
        return $this->logToBotEvents($bot, 'bot.stateChange', BotEvent::LEVEL_DEBUG, [
            'msg'   => "Entered state {$new_state}",
            'state' => $new_state,
        ]);

    }



    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function logToBotEventsWithoutEventLog(Bot $bot, $event_name, $level, $event_data) {
        return $this->logToBotEvents($bot, $event_name, $level, $event_data, false);
    }

    public function logToBotEvents(Bot $bot, $event_name, $level, $event_data, $log_to_event_log = true) {
        if ($log_to_event_log) { EventLog::log($event_name, $event_data); }

        $event_data['name'] = $event_name;
        return $this->createBotEvent($bot, $level, $event_data);
    }

    public function createBotEvent($bot, $level, $event_data) {
        $create_vars = [
            'bot_id' => $bot['id'],
            'level'  => $level,
            'event'  => $event_data,
        ];

        // create the bot event
        $bot_event_model = $this->bot_event_repository->create($create_vars);

        // fire an event
        Event::fire(new BotEventCreated($bot, $bot_event_model->serializeForAPI()));

        return $bot_event_model;

    }



}
