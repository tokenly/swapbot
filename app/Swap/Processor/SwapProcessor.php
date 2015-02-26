<?php

namespace Swapbot\Swap\Processor;

use ArrayObject;
use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Swap;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Exception\SwapStrategyException;
use Swapbot\Swap\Factory\StrategyFactory;
use Swapbot\Swap\Logger\BotEventLogger;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\Client;

class SwapProcessor {

    // const RESULT_IGNORED   = 0;
    // const RESULT_PROCESSED = 1;
    // const RESULT_SENT      = 2;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(Client $xchain_client, SwapRepository $swap_repository, StrategyFactory $strategy_factory, BotEventLogger $bot_event_logger)
    {
        $this->xchain_client          = $xchain_client;
        $this->swap_repository        = $swap_repository;
        $this->strategy_factory       = $strategy_factory;
        $this->bot_event_logger       = $bot_event_logger;
    }

    public function processSwapConfig(SwapConfig $swap_config, $tx_process) {
        // ignore an asset that doesn't match
        if ($tx_process['xchain_notification']['asset'] != $swap_config['in']) { return false; }

        // load or create the swap from the database
        $swap = $this->findOrCreateSwap($swap_config, $tx_process);

        try {
            // initialize a DTO (data transfer object) to hold all the variables for this swap
            $swap_process = new ArrayObject([
                'swap'             => $swap,
                'swap_config'      => $swap_config,
                'swap_id'          => $swap_config->buildName(),
                'quantity'         => null,
                'asset'            => null,
                'swap_was_handled' => false,

                'swap_update_vars' => [],
            ]);

            // calculate the receipient's quantity and asset
            list($swap_process['quantity'], $swap_process['asset']) = $swap_process['swap_config']->getStrategy()->buildSwapOutputQuantityAndAsset($swap_process['swap_config'], $tx_process['xchain_notification']);

            // handle an unconfirmed TX
            $this->handleUnconfirmedTX($swap_process, $tx_process);

            // see if the swap has already been handled
            $this->handlePreviouslyProcessedSwap($swap_process, $tx_process);

            // if all the checks above passed
            //   then we should process this swap
            if (!$swap_process['swap_was_handled']) {
                $this->doSwap($swap_process, $tx_process);
            }

            // if anything was updated, then update the swap
            $this->handleUpdateSwapModel($swap_process);

        } catch (Exception $e) {
            // log any failure
            if ($e instanceof SwapStrategyException) {
                EventLog::logError('swap.failed', $e);
                $this->bot_event_logger->logToBotEventsWithoutEventLog($tx_process['bot'], $e->getErrorName(), $e->getErrorLevel(), $e->getErrorData());
            } else {
                EventLog::logError('swap.failed', $e);
                $this->bot_event_logger->logSwapFailed($tx_process['bot'], $tx_process['xchain_notification'], $e);
            }
            $tx_process['any_notification_given'] = true;
        }

        // processed this swap
        return $swap;
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function handleUnconfirmedTX($swap_process, $tx_process) {
        if ($swap_process['swap_was_handled']) { return; }

        // is this an unconfirmed tx?
        if (!$tx_process['is_confirmed']) {
            $swap_process['swap_was_handled'] = true;
            $this->bot_event_logger->logUnconfirmedTx($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset']);
            $tx_process['any_notification_given'] = true;
        }
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function handlePreviouslyProcessedSwap($swap_process, $tx_process) {
        if ($swap_process['swap_was_handled']) { return; }

        if ($swap_process['swap']['processed']) {
            // this swap has already been processed
            // don't process it
            $swap_process['swap_was_handled'] = true;

            // log as previously processed
            $this->bot_event_logger->logPreviouslyProcessedSwap($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset']);
            $tx_process['any_notification_given'] = true;
        }
    }
    
    protected function doSwap($swap_process, $tx_process) {
        // log the attempt to send
        $this->bot_event_logger->logSendAttempt($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset'], $tx_process['confirmations']);

        // send it
        try {
            $send_result = $this->sendAssets($tx_process['bot'], $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset']);
        } catch (Exception $e) {
            // $tx_process['any_processing_errors'] = true;
            throw $e;
        }

        // update the swap receipts in memory
        $swap_process['swap_update_vars']['processed'] = true;
        $swap_process['swap_update_vars']['receipt'] = [
            'txid'          => $send_result['txid'],
            'confirmations' => $tx_process['confirmations']
        ];
        // move the swap into the sent state
        $swap_process['swap_update_vars']['state'] = 'sent';

        $this->bot_event_logger->logSendResult($tx_process['bot'], $send_result, $tx_process['xchain_notification'], $tx_process['destination'], $swap_process['quantity'], $swap_process['asset'], $tx_process['confirmations']);
        $tx_process['any_notification_given'] = true;
    }

    protected function handleUpdateSwapModel($swap_process) {
        if ($swap_process['swap_update_vars']) {
            $this->swap_repository->update($swap_process['swap'], $swap_process['swap_update_vars']);
        }
    }

    protected function sendAssets($bot, $xchain_notification, $destination, $quantity, $asset) {
        // call xchain
        $fee = $bot['return_fee'];
        $send_result = $this->xchain_client->send($bot['public_address_id'], $destination, $quantity, $asset, $fee);

        return $send_result;
    }



    protected function findOrCreateSwap($swap_config, $tx_process) {
        // swap variables
        $bot_id         = $tx_process['bot']['id'];
        $transaction_id = $tx_process['transaction']['id'];
        $swap_name      = $swap_config->buildName();

        // try to find an existing swap
        $existing_swap = $this->swap_repository->findByBotIDTransactionIDAndName($bot_id, $transaction_id, $swap_name);
        if ($existing_swap) { return $existing_swap; }

        // no swap exists yet, so create one
        $new_swap = $this->swap_repository->create([
            'name'           => $swap_config->buildName(),
            'definition'     => $swap_config->serialize(),
            'processed'      => false,
            'state'          => 'brandnew',
            'bot_id'         => $bot_id,
            'transaction_id' => $transaction_id,
        ]);

        return $new_swap;
    }

}
