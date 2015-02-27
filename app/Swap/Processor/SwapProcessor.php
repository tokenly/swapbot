<?php

namespace Swapbot\Swap\Processor;

use ArrayObject;
use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ReconcileSwapState;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapStateEvent;
use Swapbot\Models\Swap;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Exception\SwapStrategyException;
use Swapbot\Swap\Factory\StrategyFactory;
use Swapbot\Swap\Logger\BotEventLogger;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\Client;

class SwapProcessor {

    use DispatchesCommands;

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


    public function processSwapConfig(SwapConfig $swap_config, $bot_id, $transaction_id) {
        // load or create the swap from the database
        $swap = $this->findOrCreateSwap($swap_config, $bot_id, $transaction_id);

        return $this->processSwap($swap);
    }

    public function processSwap(Swap $swap) {
        try {
            // start by reconciling the swap state
            $this->dispatch(new ReconcileSwapState($swap));

            $swap_config = $swap->getSwapConfig();

            // get the transaction, bot and the xchain notification
            $transaction         = $swap->transaction;
            $bot                 = $swap->bot;
            $xchain_notification = $transaction['xchain_notification'];

            // initialize a DTO (data transfer object) to hold all the variables for this swap
            $swap_process = new ArrayObject([
                'swap'                   => $swap,
                'swap_config'            => $swap_config,
                'swap_id'                => $swap_config->buildName(),

                'transaction'            => $transaction,
                'bot'                    => $bot,

                'xchain_notification'    => $xchain_notification,
                'in_quantity'            => $xchain_notification['quantity'],
                'destination'            => $xchain_notification['sources'][0],
                'confirmations'          => $xchain_notification['confirmations'],
                'is_confirmed'           => $xchain_notification['confirmed'],

                'quantity'               => null,
                'asset'                  => null,
                'swap_was_handled'       => false,

                'swap_update_vars'       => [],
                'state_trigger'          => [],
            ]);

            // calculate the receipient's quantity and asset
            list($swap_process['quantity'], $swap_process['asset']) = $swap_process['swap_config']->getStrategy()->buildSwapOutputQuantityAndAsset($swap_process['swap_config'], $swap_process['in_quantity']);


            // handle an unconfirmed TX
            $this->handleUnconfirmedTX($swap_process);

            // see if the swap has already been handled
            $this->handlePreviouslyProcessedSwap($swap_process);

            // if all the checks above passed
            //   then we should process this swap
            if (!$swap_process['swap_was_handled']) {
                $this->doSwap($swap_process);
            }

            // if anything was updated, then update the swap
            $this->handleUpdateSwapModel($swap_process);

        } catch (Exception $e) {
            // log any failure
            if ($e instanceof SwapStrategyException) {
                EventLog::logError('swap.failed', $e);
                $this->bot_event_logger->logToBotEventsWithoutEventLog($swap_process['bot'], $e->getErrorName(), $e->getErrorLevel(), $e->getErrorData());
            } else {
                EventLog::logError('swap.failed', $e);
                $this->bot_event_logger->logSwapFailed($swap_process['bot'], $swap_process['xchain_notification'], $e);
            }
        }

        // processed this swap
        return $swap;
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function handleUnconfirmedTX($swap_process) {
        if ($swap_process['swap_was_handled']) { return; }

        // is this an unconfirmed tx?
        if (!$swap_process['is_confirmed']) {
            $swap_process['swap_was_handled'] = true;
            $this->bot_event_logger->logUnconfirmedTx($swap_process['bot'], $swap_process['xchain_notification'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset']);
        }
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function handlePreviouslyProcessedSwap($swap_process) {
        if ($swap_process['swap_was_handled']) { return; }

        if ($swap_process['swap']->wasSent()) {
            // this swap has already been processed
            // don't process it
            $swap_process['swap_was_handled'] = true;

            // log as previously processed
            $this->bot_event_logger->logPreviouslyProcessedSwap($swap_process['bot'], $swap_process['xchain_notification'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset']);
        }
    }
    
    protected function doSwap($swap_process) {
        if (!$swap_process['swap']->isReady()) {
            $this->bot_event_logger->logSwapNotReady($swap_process['bot'], $swap_process['transaction']['id'], $swap_process['swap']['name'], $swap_process['swap']['id']);
            return;
        }

        // log the attempt to send
        $this->bot_event_logger->logSendAttempt($swap_process['bot'], $swap_process['xchain_notification'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset'], $swap_process['confirmations']);

        // send it
        try {
            $send_result = $this->sendAssets($swap_process['bot'], $swap_process['xchain_notification'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset']);
        } catch (Exception $e) {
            throw $e;
        }

        // update the swap receipts
        $swap_process['swap_update_vars']['receipt'] = [
            'txid'          => $send_result['txid'],
            'confirmations' => $swap_process['confirmations']
        ];

        // move the swap into the sent state
        $swap_process['state_trigger'] = SwapStateEvent::SWAP_SENT;

        $this->bot_event_logger->logSendResult($swap_process['bot'], $send_result, $swap_process['xchain_notification'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset'], $swap_process['confirmations']);
    }

    protected function handleUpdateSwapModel($swap_process) {
        // update the swap
        if ($swap_process['swap_update_vars']) {
            $this->swap_repository->update($swap_process['swap'], $swap_process['swap_update_vars']);
        }

        // also trigger a state change
        if ($swap_process['state_trigger']) {
            $swap_process['swap']->stateMachine()->triggerEvent($swap_process['state_trigger']);
        }

    }

    protected function sendAssets($bot, $xchain_notification, $destination, $quantity, $asset) {
        // call xchain
        $fee = $bot['return_fee'];
        $send_result = $this->xchain_client->send($bot['public_address_id'], $destination, $quantity, $asset, $fee);

        return $send_result;
    }



    protected function findOrCreateSwap($swap_config, $bot_id, $transaction_id) {
        // swap variables
        $swap_name      = $swap_config->buildName();

        // try to find an existing swap
        $existing_swap = $this->swap_repository->findByBotIDTransactionIDAndName($bot_id, $transaction_id, $swap_name);
        if ($existing_swap) { return $existing_swap; }

        // no swap exists yet, so create one
        $new_swap = $this->swap_repository->create([
            'name'           => $swap_config->buildName(),
            'definition'     => $swap_config->serialize(),
            'state'          => 'brandnew',
            'bot_id'         => $bot_id,
            'transaction_id' => $transaction_id,
        ]);

        return $new_swap;
    }

}
