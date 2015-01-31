<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\CreateBotEvent;
use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Commands\UpdateBotBalances;
use Swapbot\Models\BotEvent;
use Swapbot\Providers\EventLog\Facade\EventLog;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\TransactionRepository;
use Tokenly\XChainClient\Client;

class ReceiveWebhookHandler {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, TransactionRepository $transaction_repository, Client $xchain_client)
    {
        $this->bot_repository         = $bot_repository;
        $this->transaction_repository = $transaction_repository;
        $this->xchain_client          = $xchain_client;
    }

    /**
     * Handle the command.
     *
     * @param  ReceiveWebhook  $command
     * @return void
     */
    public function handle(ReceiveWebhook $command)
    {
        $payload = $command->payload;

        switch ($payload['event']) {
            case 'block':
                // new block event
                //  don't do anything here
                EventLog::log('block.received', $payload);
                break;

            case 'receive':
                // new receive event
                $bot = $this->handleReceive($payload);
                break;

            case 'send':
                // new send event
                $bot = $this->handleSend($payload);
                break;

            default:
                EventLog::log('event.unknown', "Unknown event type: {$payload['event']}");
        }
    }

    protected function handleReceive($xchain_notification) {
        // find the bot related to this notification
        $bot = $this->bot_repository->findByMonitorID($xchain_notification['notifiedAddressId']);
        if (!$bot) { throw new Exception("Unable to find bot for monitor {$xchain_notification['notifiedAddressId']}", 1); }

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
        $should_update_bot_balance     = false;


        // previously processed
        if ($should_process AND $transaction_model['processed']) {
            $this->logToBotEvents($bot, 'swap.previous', BotEvent::LEVEL_DEBUG, [
                'msg'  => "Transaction {$xchain_notification['txid']} has already been processed.  Ignoring it.",
                'txid' => $xchain_notification['txid']
            ]);
            $should_process = false;
        }


        // check for blacklisted sources (for confirmed transactions)
        if ($should_process AND $is_confirmed AND !$transaction_model['processed']) {
            $blacklist_addresses = $bot['blacklist_addresses'];

            // never send to self
            $blacklist_addresses[] = $xchain_notification['notifiedAddress'];
            
            if (in_array($xchain_notification['sources'][0], $blacklist_addresses)) {
                // blacklisted
                $this->logSendFromBlacklistedAddress($bot, $xchain_notification);

                $should_process = false;
                $should_update_transaction = true;
                $should_update_bot_balance = true;
            }
        }


        // process all relevant swaps for transactions that have not been processed yet
        if ($should_process AND !$transaction_model['processed']) {

            foreach ($bot['swaps'] as $swap) {
                if ($xchain_notification['asset'] == $swap['in']) {
                    try {
                        // we recieved an asset - exchange 'in' for 'out'

                        // determine the swap ID
                        $swap_id = $bot->buildSwapID($swap);

                        // calculate the receipient's quantity and asset
                        $quantity = $xchain_notification['quantity'] * $swap['rate'];
                        $asset = $swap['out'];


                        // should we process this swap?
                        $should_process_swap = true;

                        // is this an unconfirmed tx?
                        if (!$is_confirmed) {
                            $should_process_swap = false;
                            $this->logUnconfirmedTx($bot, $xchain_notification, $destination, $quantity, $asset);
                        }

                        // is the bot active?
                        if ($should_process_swap AND !$bot['active']) {
                            $should_process_swap = false;

                            // mark the transaction as processed
                            //   even though the bot was inactive
                            $should_update_transaction = true;
                            $should_update_bot_balance = true;

                            // log the inactive bot status
                            $this->logInactiveBot($bot, $xchain_notification);
                        }


                        // see if the swap has already been handled
                        if ($should_process_swap AND isset($swap_receipts[$swap_id]) AND $swap_receipts[$swap_id]['txid']) {
                            $should_process_swap = false;

                            // this swap receipt already exists
                            $this->logPreviouslyProcessedSwap($bot, $xchain_notification, $destination, $quantity, $asset);
                        }



                        // if all the checks above passed
                        //   then we should process this swap
                        if ($should_process_swap) {
                            // log the attempt to send
                            $this->logSendAttempt($bot, $xchain_notification, $destination, $quantity, $asset, $confirmations);

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
                            $should_update_bot_balance = true;

                            $this->logSendResult($bot, $send_result, $xchain_notification, $destination, $quantity, $asset, $confirmations);
                        }


                    } catch (Exception $e) {
                        // log any failure
                        EventLog::logError('swap.failed', $e);
                        $this->logSwapFailed($bot, $xchain_notification, $e);
                    }
                }
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

        if ($should_update_bot_balance) {
            $this->updateBotBalance($bot);
        }

        return $bot;
    }

    protected function handleSend($xchain_notification) {
        // find the bot related to this notification
        $bot = $this->bot_repository->findByMonitorID($xchain_notification['notifiedAddressId']);
        if (!$bot) { throw new Exception("Unable to find bot for monitor {$xchain_notification['notifiedAddressId']}", 1); }

        // load or create a new transaction from the database
        $transaction_model = $this->findOrCreateTransaction($xchain_notification['txid'], $bot['id']);
        if (!$transaction_model) { throw new Exception("Unable to access database", 1); }

        // setup variables
        $should_process            = true;
        $should_update_transaction = false;
        $should_update_bot_balance = false;


        // previously processed
        if ($should_process AND $transaction_model['processed']) {
            $this->logToBotEvents($bot, 'send.previous', BotEvent::LEVEL_DEBUG, [
                'msg'  => "Send transaction {$xchain_notification['txid']} has already been processed.  Ignoring it.",
                'txid' => $xchain_notification['txid']
            ]);
            $should_process = false;
        }


        // just log it
        if ($should_process) {
            // determine the number of confirmations
            $confirmations = $xchain_notification['confirmations'];
            $is_confirmed = $xchain_notification['confirmed'];
            $quantity = $xchain_notification['quantity'];
            $asset = $xchain_notification['asset'];
            $destination = $xchain_notification['destinations'][0];

            if ($is_confirmed AND !$transaction_model['processed']) {
                $this->logConfirmedSendTx($bot, $xchain_notification, $destination, $quantity, $asset, $confirmations);
                $should_update_transaction = true;
                $should_update_bot_balance = true;
            } else {
                $this->logUnconfirmedSendTx($bot, $xchain_notification, $destination, $quantity, $asset);
            }

            if ($should_update_transaction) {
                // mark the transaction as processed
                $update_vars = [];
                $update_vars['processed'] = true;
                $update_vars['confirmations'] = $confirmations;

                $this->transaction_repository->update($transaction_model, $update_vars);
            }
        }

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
            $this->logBalanceUpdateFailed($bot, $e);
        }
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Desc
    
    protected function sendAssets($bot, $xchain_notification, $destination, $quantity, $asset) {
        // call xchain
        $send_result = $this->xchain_client->send($bot['payment_address_id'], $destination, $quantity, $asset);

        return $send_result;
    }

    protected function findOrCreateTransaction($txid, $bot_id) {
        $transaction_model = $this->transaction_repository->findByTransactionIDAndBotID($txid, $bot_id);
        if ($transaction_model) { return $transaction_model; }

        // create a new transaction
        return $this->transaction_repository->create(['bot_id' => $bot_id, 'txid' => $txid]);
    }

    protected function logToBotEventsWithoutEventLog($bot, $event_name, $level, $event_data) {
        return $this->logToBotEvents($bot, $event_name, $level, $event_data, false);
    }

    protected function logToBotEvents($bot, $event_name, $level, $event_data, $log_to_event_log = true) {
        if ($log_to_event_log) { EventLog::log($event_name, $event_data); }

        $event_data['name'] = $event_name;
        $this->dispatch(new CreateBotEvent($bot, $level, $event_data));
    }




    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Desc
    
    protected function logSendAttempt($bot, $xchain_notification, $destination, $quantity, $asset, $confirmations) {
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
    
    protected function logSendResult($bot, $send_result, $xchain_notification, $destination, $quantity, $asset, $confirmations) {
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

    protected function logUnconfirmedTx($bot, $xchain_notification, $destination, $quantity, $asset) {
        $this->logToBotEvents($bot, 'unconfirmed.tx', BotEvent::LEVEL_DEBUG, [
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

    protected function logInactiveBot($bot, $xchain_notification) {
        $this->logToBotEvents($bot, 'bot.inactive', BotEvent::LEVEL_INFO, [
            'msg'  => "Ignored transaction {$xchain_notification['txid']} because this bot is inactive.",
            'txid' => $xchain_notification['txid']
        ]);
    }

    protected function logPreviouslyProcessedSwap($bot, $xchain_notification, $destination, $quantity, $asset) {
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

    protected function logSendFromBlacklistedAddress($bot, $xchain_notification) {
        $this->logToBotEvents($bot, 'swap.ignored.blacklist', BotEvent::LEVEL_INFO, [
            'msg'         => "Ignored transaction of {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]} because sender address was blacklisted.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'inQty'       => $xchain_notification['quantity'],
            'inAsset'     => $xchain_notification['asset'],
        ]);
    }

    protected function logUnconfirmedSendTx($bot, $xchain_notification, $destination, $quantity, $asset) {
        $this->logToBotEvents($bot, 'send.unconfirmed', BotEvent::LEVEL_DEBUG, [
            'msg'         => "Saw unconfirmed send of {$quantity} {$asset} to {$destination} with transaction ID {$xchain_notification['txid']}.",
            'txid'        => $xchain_notification['txid'],
            'source'      => $xchain_notification['sources'][0],
            'outQty'      => $quantity,
            'outAsset'    => $asset,
            'destination' => $destination,
        ]);
    }

    protected function logConfirmedSendTx($bot, $xchain_notification, $destination, $quantity, $asset, $confirmations) {
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

    protected function logSwapFailed($bot, $xchain_notification, $e) {
        $this->logToBotEventsWithoutEventLog($bot, 'swap.failed', BotEvent::LEVEL_WARNING, [
            'msg'         => "Failed to swap asset. ".$e->getMessage(),
            'txid'        => $xchain_notification['txid'],
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
        ]);
    }

    protected function logBalanceUpdateFailed($bot, $e) {
        $this->logToBotEventsWithoutEventLog($bot, 'balanceupdate.failed', BotEvent::LEVEL_WARNING, [
            'msg'         => "Failed to update balances. ".$e->getMessage(),
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
        ]);
    }

}