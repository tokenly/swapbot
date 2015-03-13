<?php

namespace Swapbot\Swap\Processor;

use ArrayObject;
use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\BillBotForTransaction;
use Swapbot\Commands\ReconcileSwapState;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Data\SwapStateEvent;
use Swapbot\Models\Swap;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Exception\SwapStrategyException;
use Swapbot\Swap\Factory\StrategyFactory;
use Swapbot\Swap\Logger\BotEventLogger;
use Swapbot\Swap\Processor\Util\BalanceUpdater;
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
    public function __construct(Client $xchain_client, SwapRepository $swap_repository, BotRepository $bot_repository, StrategyFactory $strategy_factory, BotEventLogger $bot_event_logger, BalanceUpdater $balance_updater)
    {
        $this->xchain_client    = $xchain_client;
        $this->swap_repository  = $swap_repository;
        $this->bot_repository   = $bot_repository;
        $this->strategy_factory = $strategy_factory;
        $this->bot_event_logger = $bot_event_logger;
        $this->balance_updater  = $balance_updater;
    }


    public function getSwapFromSwapConfig(SwapConfig $swap_config, $bot_id, $transaction_id) {
        // load or create the swap from the database
        return $this->findOrCreateSwap($swap_config, $bot_id, $transaction_id);
    }

    public function processSwap(Swap $swap) {
        try {

            // start by checking the status of the bot
            //   if the bot is not active, don't process anything else
            $bot = $swap->bot;
            if (!$this->botIsActive($bot)) { return $swap; }

            // start by reconciling the swap state
            $this->dispatch(new ReconcileSwapState($swap));

            $swap_config = $swap->getSwapConfig();

            // get the transaction, bot and the xchain notification
            $transaction         = $swap->transaction;
            $xchain_notification = $transaction['xchain_notification'];

            // initialize a DTO (data transfer object) to hold all the variables for this swap
            $swap_process = new ArrayObject([
                'swap'                => $swap,
                'swap_config'         => $swap_config,
                'swap_id'             => $swap_config->buildName(),

                'transaction'         => $transaction,
                'bot'                 => $bot,

                'xchain_notification' => $xchain_notification,
                'in_quantity'         => $xchain_notification['quantity'],
                'destination'         => $xchain_notification['sources'][0],
                'confirmations'       => $transaction['confirmations'],
                'is_confirmed'        => $xchain_notification['confirmed'],

                'quantity'            => null,
                'asset'               => null,
                'swap_was_handled'    => false,

                'swap_update_vars'    => [],
                'bot_balance_deltas'  => [],
                'state_trigger'       => false,
                'swap_was_sent'       => false,
            ]);

            // calculate the receipient's quantity and asset
            list($swap_process['quantity'], $swap_process['asset']) = $swap_process['swap_config']->getStrategy()->buildSwapOutputQuantityAndAsset($swap_process['swap_config'], $swap_process['in_quantity']);

            // check the swap state
            $this->resetSwapStateForProcessing($swap_process);

            // handle an unconfirmed TX
            $this->handleUnconfirmedTX($swap_process);

            // see if the swap has already been handled
            $this->handlePreviouslyProcessedSwap($swap_process);

            // see if the swap is still confirming
            $this->handleUnconfirmedSwap($swap_process);

            // if all the checks above passed
            //   then we should process this swap
            $this->doSwap($swap_process);

            // if anything was updated, then update the swap
            $this->handleUpdateSwapModel($swap_process);

            // also update the bot balances
            $this->handleUpdateBotBalances($swap_process);

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

        // if a state change was triggered, then update the swap state
        //   this can happen even if there was an error
        $this->handleUpdateSwapState($swap_process);

        // if the swaps are finished, then bill the bot
        $this->handleBotBilling($swap_process);

        // processed this swap
        return $swap;
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function resetSwapStateForProcessing($swap_process) {
        if ($swap_process['swap_was_handled']) { return; }

        $swap = $swap_process['swap'];
        switch ($swap['state']) {
            case SwapState::ERROR:
                // the swap errored last time
                //   switch the swap back to the ready state
                //   in order to try again
                $this->bot_event_logger->logSwapRetry($swap_process['bot'], $swap_process['swap']);
                $swap_process['swap']->stateMachine()->triggerEvent(SwapStateEvent::SWAP_RETRY);
                break;
        }
    }

    protected function botIsActive($bot) {
        return  $bot->statemachine()->getCurrentState()->isActive();
        // $this->bot_event_logger->logBotNotReadyForSwap($swap_process['bot'], $swap_process['swap'], $bot_state->getName());
    }

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
    
    protected function handleUnconfirmedSwap($swap_process) {
        if ($swap_process['swap_was_handled']) { return; }

        if ($swap_process['confirmations'] < $swap_process['bot']['confirmations_required']) {
            // move the swap into the confirming state
            $swap_process['state_trigger'] = SwapStateEvent::CONFIRMING;
            
            // don't process it any further
            $swap_process['swap_was_handled'] = true;

            // log as confirming
            $this->bot_event_logger->logConfirmingSwap($swap_process['bot'], $swap_process['xchain_notification'], $swap_process['confirmations'], $swap_process['bot']['confirmations_required'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset']);
        } else if ($swap_process['confirmations'] >= $swap_process['bot']['confirmations_required']) {
            if ($swap_process['swap']->isConfirming()) {
                // the swap just became confirmed
                //   update the state right now
                $this->bot_event_logger->logConfirmedSwap($swap_process['bot'], $swap_process['xchain_notification'], $swap_process['confirmations'], $swap_process['bot']['confirmations_required'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset']);
                $swap_process['swap']->stateMachine()->triggerEvent(SwapStateEvent::CONFIRMED);
            }
        }

    }
    
    protected function doSwap($swap_process) {
        if ($swap_process['swap_was_handled']) { return; }

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
            // move the swap into an error state
            $swap_process['state_trigger'] = SwapStateEvent::SWAP_ERRORED;

            throw $e;
        }

        // update the swap receipts
        $swap_process['swap_update_vars']['receipt'] = [
            'txid'          => $send_result['txid'],
            'confirmations' => $swap_process['confirmations']
        ];

        // update the local balance
        $swap_process['bot_balance_deltas'] = $this->updateBalanceDeltasFromProcessedSwap($swap_process, $swap_process['bot_balance_deltas']);

        // move the swap into the sent state
        $swap_process['state_trigger'] = SwapStateEvent::SWAP_SENT;

        // log it
        $this->bot_event_logger->logSendResult($swap_process['bot'], $send_result, $swap_process['xchain_notification'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset'], $swap_process['confirmations']);

        // mark the swap as sent
        $swap_process['swap_was_sent'] = true;
    }

    protected function updateBalanceDeltasFromProcessedSwap($swap_process, $bot_balance_deltas) {
        $asset = $swap_process['asset'];
        $quantity = $swap_process['quantity'];
        $btc_fee = $swap_process['bot']['return_fee'];

        // deduct the asset balance
        if (!isset($bot_balance_deltas[$asset])) { $bot_balance_deltas[$asset] = 0; }
        $bot_balance_deltas[$asset] = $bot_balance_deltas[$asset] - $quantity;

        // deduct the BTC fee
        if (!isset($bot_balance_deltas['BTC'])) { $bot_balance_deltas['BTC'] = 0; }
        $bot_balance_deltas['BTC'] = $bot_balance_deltas['BTC'] - $btc_fee;

        return $bot_balance_deltas;
    }

    protected function handleUpdateSwapModel($swap_process) {
        // update the swap
        if ($swap_process['swap_update_vars']) {
            $this->swap_repository->update($swap_process['swap'], $swap_process['swap_update_vars']);
        }
    }

    protected function handleUpdateSwapState($swap_process) {
        // also trigger a state change
        if ($swap_process['state_trigger']) {
            $swap_process['swap']->stateMachine()->triggerEvent($swap_process['state_trigger']);
        }

    }

    protected function handleBotBilling($swap_process) {
        // billing only happens when the last swap is sent
        if (!$swap_process['swap_was_sent']) { return; }

        // bill the bot if all the swaps are now complete for this transaction
        if ($this->allSwapsAreComplete($swap_process['transaction'])) {
            $this->billBot($swap_process['bot'], $swap_process['transaction']);
        }
    }

    protected function handleUpdateBotBalances($swap_process) {
        if ($swap_process['bot_balance_deltas']) {
            $this->balance_updater->updateBotBalances($swap_process['bot'], $swap_process['bot_balance_deltas']);
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

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Bill Bot

    protected function allSwapsAreComplete($transaction) {
        $all_swaps = $this->swap_repository->findByTransactionID($transaction['id']);
        $all_complete = true;
        if (count($all_swaps) == 0) { $all_complete = false; }

        foreach($all_swaps as $swap) {
            if (!$swap->wasSent()) {
                $all_complete = false;
                break;
            }
        }

        return $all_complete;
    }

    protected function billBot($bot, $transaction) {
        $this->dispatch(new BillBotForTransaction($bot, $transaction));
    }

    
    

}
