<?php

namespace Swapbot\Swap\Processor;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Swap\Exception\SwapStrategyException;
use Swapbot\Swap\Factory\StrategyFactory;
use Swapbot\Swap\Logger\BotEventLogger;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\Client;
use ArrayObject;

class SwapProcessor {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(Client $xchain_client, StrategyFactory $strategy_factory, BotEventLogger $swap_event_logger)
    {
        $this->xchain_client          = $xchain_client;
        $this->strategy_factory       = $strategy_factory;
        $this->swap_event_logger      = $swap_event_logger;
    }

    public function processSwap($swap, $tx_process) {
        // ignore an asset that doesn't match
        if ($tx_process['xchain_notification']['asset'] != $swap['in']) { return false; }

        try {
            // initialize a DTO (data transfer object) to hold all the variables for this swap
            $swap_process = new ArrayObject([
                'strategy'            => $this->strategy_factory->newStrategy($swap['strategy']),
                'swap_id'             => $tx_process['bot']->buildSwapID($swap),
                'quantity'            => null,
                'asset'               => null,
                'should_process_swap' => true,
            ]);

            // calculate the receipient's quantity and asset
            list($swap_process['quantity'], $swap_process['asset']) = $swap_process['strategy']->buildSwapOutputQuantityAndAsset($swap, $tx_process['xchain_notification']);

            // handle an unconfirmed TX
            $this->handleUnconfirmedTX($swap_process, $tx_process);

            // see if the swap has already been handled
            $this->handlePreviouslyProcessedSwap($swap_process, $tx_process);

            // if all the checks above passed
            //   then we should process this swap
            if ($swap_process['should_process_swap']) {
                $this->doSwap($swap_process, $tx_process);
            }


        } catch (Exception $e) {
            // log any failure
            if ($e instanceof SwapStrategyException) {
                EventLog::logError('swap.failed', $e);
                $this->swap_event_logger->logToBotEventsWithoutEventLog($tx_process['bot'], $e->getErrorName(), $e->getErrorLevel(), $e->getErrorData());
            } else {
                EventLog::logError('swap.failed', $e);
                $this->swap_event_logger->logSwapFailed($tx_process['bot'], $tx_process['xchain_notification'], $e);
            }
            $tx_process['any_notification_given'] = true;
        }

        // processed this swap
        return true;
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function handleUnconfirmedTX($swap_process, $tx_process) {
        // is this an unconfirmed tx?
        if (!$tx_process['is_confirmed']) {
            $swap_process['should_process_swap'] = false;
            $this->swap_event_logger->logUnconfirmedTx($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset']);
            $tx_process['any_notification_given'] = true;
        }
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function sendAssets($bot, $xchain_notification, $destination, $quantity, $asset) {
        // call xchain
        $fee = $bot['return_fee'];
        $send_result = $this->xchain_client->send($bot['public_address_id'], $destination, $quantity, $asset, $fee);

        return $send_result;
    }

    protected function handlePreviouslyProcessedSwap($swap_process, $tx_process) {
        if ($swap_process['should_process_swap'] AND isset($tx_process['swap_receipts'][$swap_process['swap_id']]) AND $tx_process['swap_receipts'][$swap_process['swap_id']]['txid']) {
            $swap_process['should_process_swap'] = false;

            // this swap receipt already exists
            $this->swap_event_logger->logPreviouslyProcessedSwap($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset']);
            $tx_process['any_notification_given'] = true;
        }
    }
    
    protected function doSwap($swap_process, $tx_process) {
        // log the attempt to send
        $this->swap_event_logger->logSendAttempt($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset'], $tx_process['confirmations']);

        // send it
        try {
            $send_result = $this->sendAssets($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset'], $tx_process['swap_receipts']);
        } catch (Exception $e) {
            $tx_process['any_processing_errors'] = true;
            throw $e;
        }

        // update the swap receipts in memory
        $tx_process['swap_receipts'][$swap_process['swap_id']] = ['txid' => $send_result['txid'], 'confirmations' => $tx_process['confirmations']];

        // mark any processed
        $tx_process['should_update_transaction'] = true;

        $this->swap_event_logger->logSendResult($tx_process['bot'], $send_result, $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset'], $tx_process['confirmations']);
        $tx_process['any_notification_given'] = true;

        // mark the swap as executed
        $tx_process['any_swap_executed'] = true;
    }

}
