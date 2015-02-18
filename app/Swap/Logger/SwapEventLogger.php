<?php

namespace Swapbot\Swap\Logger;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\CreateBotEvent;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;
use Tokenly\LaravelEventLog\Facade\EventLog;

class SwapEventLogger {

    use DispatchesCommands;

    /**
     */
    public function __construct()
    {
    }

    public function logSendAttempt(Bot $bot, $xchain_notification, $destination, $quantity, $asset, $confirmations) {
        // log the send
        $this->logToBotEvents($bot, 'swap.found', BotEvent::LEVEL_DEBUG, [
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
        $this->logToBotEvents($bot, 'swap.sent', BotEvent::LEVEL_INFO, [
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
        $this->logToBotEvents($bot, 'unconfirmed.tx', BotEvent::LEVEL_INFO, [
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

    public function logInactiveBot(Bot $bot, $xchain_notification) {
        $this->logToBotEvents($bot, 'bot.inactive', BotEvent::LEVEL_INFO, [
            'msg'  => "Ignored transaction {$xchain_notification['txid']} because this bot is inactive.",
            'txid' => $xchain_notification['txid']
        ]);
    }

    public function logPreviouslyProcessedSwap(Bot $bot, $xchain_notification, $destination, $quantity, $asset) {
        $this->logToBotEvents($bot, 'swap.processed.previous', BotEvent::LEVEL_DEBUG, [
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
        $this->logToBotEvents($bot, 'swap.ignored.blacklist', BotEvent::LEVEL_INFO, [
            'msg'         => "Ignored ".($is_confirmed?'':'unconfirmed ')."transaction of {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]} because sender address was blacklisted.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
        ]);
    }

    public function logUnconfirmedSendTx(Bot $bot, $xchain_notification, $destination, $quantity, $asset) {
        $this->logToBotEvents($bot, 'send.unconfirmed', BotEvent::LEVEL_DEBUG, [
            'msg'         => "Saw unconfirmed send of {$quantity} {$asset} to {$destination} with transaction ID {$xchain_notification['txid']}.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'outQty'      => $quantity,
            'outAsset'    => $asset,
            'destination' => $destination,
        ]);
    }

    public function logConfirmedSendTx(Bot $bot, $xchain_notification, $destination, $quantity, $asset, $confirmations) {
        $this->logToBotEvents($bot, 'send.confirmed', BotEvent::LEVEL_INFO, [
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

        $this->logToBotEvents($bot, 'receive.unknown', BotEvent::LEVEL_INFO, [
            'msg'           => "Received {$quantity} {$asset} with transaction ID {$xchain_notification['txid']}.  This transaction did not trigger any swaps.",
            'txid'          => $xchain_notification['txid'],
            'confirmations' => $confirmations,
            'source'        => $xchain_notification['sources'][0],
            'inQty'         => $quantity,
            'inAsset'       => $asset,
        ]);
    }

    public function logUnhandledTransaction(Bot $bot, $xchain_notification) {
        $this->logToBotEvents($bot, 'tx.unhandled', BotEvent::LEVEL_WARNING, [
            'msg'           => "Transaction ID {$xchain_notification['txid']} was not handled by this swapbot.",
            'txid'          => $xchain_notification['txid'],
        ]);
    }

    public function logSwapFailed(Bot $bot, $xchain_notification, $e) {
        $this->logToBotEventsWithoutEventLog($bot, 'swap.failed', BotEvent::LEVEL_WARNING, [
            'msg'   => "Failed to swap asset.",
            'error' => $e->getMessage(),
            'txid'  => $xchain_notification['txid'],
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
    }

    public function logBalanceUpdateFailed(Bot $bot, $e) {
        $this->logToBotEventsWithoutEventLog($bot, 'balanceupdate.failed', BotEvent::LEVEL_WARNING, [
            'msg'   => "Failed to update balances.",
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
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
        $this->dispatch(new CreateBotEvent($bot, $level, $event_data));
    }





}
