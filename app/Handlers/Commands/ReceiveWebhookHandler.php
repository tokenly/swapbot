<?php namespace Swapbot\Handlers\Commands;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Queue\InteractsWithQueue;
use Swapbot\Commands\CreateBotEvent;
use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Models\BotEvent;
use Swapbot\Providers\EventLog\Facade\EventLog;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\TransactionRepository;
use Tokenly\XChainClient\Client;
use Exception;

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
                $this->handleReceive($payload);
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

        $should_process = true;

        // check for blacklisted sources
        //   (unimplemented)

        // $blacklisted_addresses = [];
        // $should_process = !in_array($xchain_notification['sources'][0], $blacklisted_addresses);
        // if (in_array($xchain_notification['notifiedAddress'], $xchain_notification['sources'])) { $should_process = false; }
        // if (!$should_process) {
        //     // log an event
        //     $this->dispatch(new CreateBotEvent($bot, $level, $event_data));
        //     simpleLog("ignoring send from {$xchain_notification['sources'][0]}");
        // }


        if ($should_process AND $transaction_model['processed']) {
            $this->logToBotEvents($bot, 'tx.processed', BotEvent::LEVEL_DEBUG, [
                'msg'  => "Transaction {$xchain_notification['txid']} has already been processed.  Ignoring it.",
                'txid' => $xchain_notification['txid']
            ]);
            $should_process = false;
        }

        if ($should_process AND !$transaction_model['processed']) {
            // this transaction has not been processed yet
            //   process all relevant swaps
            foreach ($bot['swaps'] as $swap) {
                if ($xchain_notification['asset'] == $swap['in']) {
                    try {
                        // we recieved an asset - exchange 'in' for 'out'

                        // assume the first source should get paid
                        $destination = $xchain_notification['sources'][0];

                        // calculate the receipient's quantity and asset
                        $quantity = $xchain_notification['quantity'] * $swap['rate'];
                        $asset = $swap['out'];


                        if (!$xchain_notification['confirmed']) {
                            // log an unconfirmed transaction
                            //   (but don't send it)
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

                        } else if ($bot['active']) {
                            $confirmations = $xchain_notification['confirmations'];

                            // log the attempt to send
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

                            // call xchain
                            $send_details = $this->xchain_client->send($bot['payment_address_id'], $destination, $quantity, $asset);

                            // save the transaction
                            $this->transaction_repository->update($transaction_model, [
                                'processed'      => true,
                                'processed_txid' => $send_details['txid'],
                            ]);

                            // log the send
                            $this->logToBotEvents($bot, 'swap.sent', BotEvent::LEVEL_INFO, [
                                // Received 500 LTBCOIN from SENDER01 with 1 confirmation.  Sent 0.0005 BTC to SENDER01 with transaction ID 0000000000000000000000000000001111
                                'msg'         => "Received {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]} with {$confirmations} confirmation".($confirmations==1?'':'s').". Sent {$quantity} {$asset} to {$destination} with transaction ID {$send_details['txid']}.",
                                'txid'        => $xchain_notification['txid'],
                                'source'      => $xchain_notification['sources'][0],
                                'inQty'       => $xchain_notification['quantity'],
                                'inAsset'     => $xchain_notification['asset'],
                                'destination' => $destination,
                                'outQty'      => $quantity,
                                'outAsset'    => $asset,
                                'sendTxID'    => $send_details['txid'],
                            ]);

                        } else {
                            // inactive bot

                            $this->logToBotEvents($bot, 'bot.inactive', BotEvent::LEVEL_INFO, [
                                'msg'  => "Transaction {$xchain_notification['txid']} ignored because this bot is inactive.",
                                'txid' => $xchain_notification['txid']
                            ]);

                            // mark the transaction as processed
                            //   even though the bot was inactive
                            $this->transaction_repository->update($transaction_model, [
                                'processed'      => true,
                            ]);
                        }

                    } catch (Exception $e) {
                        // log any failure
                        EventLog::logError('swap.failed', $e);
                        $this->logToBotEventsWithoutEventLog($bot, 'swap.failed', BotEvent::LEVEL_WARNING, [
                            'msg'         => "Failed to swap asset. ".$e->getMessage(),
                            'txid'        => $xchain_notification['txid'],
                            'file'        => $e->getFile(),
                            'line'        => $e->getLine(),
                        ]);
                    }
                }
            }
        }

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


}
