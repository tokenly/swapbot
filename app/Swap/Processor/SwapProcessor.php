<?php

namespace Swapbot\Swap\Processor;

use ArrayObject;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\BillBotForTransaction;
use Swapbot\Commands\ReconcileSwapState;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapState;
use Swapbot\Models\Data\SwapStateEvent;
use Swapbot\Models\Swap;
use Swapbot\Models\Transaction;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Exception\SwapStrategyException;
use Swapbot\Swap\Logger\BotEventLogger;
use Swapbot\Swap\Processor\Util\BalanceUpdater;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\Client;

class SwapProcessor {

    use DispatchesCommands;

    // const RESULT_IGNORED   = 0;
    // const RESULT_PROCESSED = 1;
    // const RESULT_SENT      = 2;

    const DEFAULT_REGULAR_DUST_SIZE = 0.00005430;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(Client $xchain_client, SwapRepository $swap_repository, BotRepository $bot_repository, BotEventLogger $bot_event_logger, BalanceUpdater $balance_updater)
    {
        $this->xchain_client    = $xchain_client;
        $this->swap_repository  = $swap_repository;
        $this->bot_repository   = $bot_repository;
        $this->bot_event_logger = $bot_event_logger;
        $this->balance_updater  = $balance_updater;
    }


    public function findSwapFromSwapConfig(SwapConfig $swap_config, $bot_id, $transaction_id) {
        // swap variables
        $swap_name      = $swap_config->buildName();

        // try to find an existing swap
        $existing_swap = $this->swap_repository->findByBotIDTransactionIDAndName($bot_id, $transaction_id, $swap_name);
        if ($existing_swap) { return $existing_swap; }

        return null;
    }

    public function createNewSwap($swap_config, Bot $bot, Transaction $transaction) {
        // swap variables
        $swap_name = $swap_config->buildName();

        // let the swap strategy initialize the receipt
        //   this locks in any quotes if quotes are used
        $strategy = $swap_config->getStrategy();
        $in_quantity = $transaction['xchain_notification']['quantity'];
        $initial_receipt_vars = $strategy->caculateInitialReceiptValues($swap_config, $in_quantity);

        // new swap vars
        $new_swap = $this->swap_repository->create([
            'name'           => $swap_config->buildName(),
            'definition'     => $swap_config->serialize(),
            'state'          => 'brandnew',
            'bot_id'         => $bot['id'],
            'transaction_id' => $transaction['id'],
            'receipt'        => $initial_receipt_vars,
        ]);

        // log the new Swap
        $this->bot_event_logger->logNewSwap($bot, $new_swap, ['txidIn' => $transaction['xchain_notification']['txid'], ]);

        return $new_swap;
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
                'in_asset'            => $xchain_notification['asset'],
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
            $swap_process['quantity'] = $swap['receipt']['quantityOut'];
            $swap_process['asset']    = $swap['receipt']['assetOut'];

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
                $data = $e->getErrorData();
                $data['swapId'] = $swap['uuid'];
                $this->bot_event_logger->logLegacyBotEventWithoutEventLog($swap_process['bot'], $e->getErrorName(), $e->getErrorLevel(), $e->getErrorData());
            } else {
                EventLog::logError('swap.failed', $e);
                $this->bot_event_logger->logSwapFailed($swap_process['bot'], $swap, $e, $swap_process['swap_update_vars']['receipt']);
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

            // mark details
            $receipt_update_vars = $this->buildReceiptUpdateVars('type', $swap_process);

            // determine if this is an update
            $any_changed = false;
            $previous_receipt = $swap_process['swap']['receipt'];
            foreach($receipt_update_vars as $k => $v) {
                if (!isset($previous_receipt[$k]) OR $v != $previous_receipt[$k]) { $any_changed = true; }
            }

            // only update if something has changed
            if ($any_changed) {
                $swap_process['swap_update_vars']['receipt'] = $receipt_update_vars;
                $this->bot_event_logger->logSwapTransactionUpdate($swap_process['bot'], $swap_process['swap'], $receipt_update_vars);
            }
        }
    }

    protected function buildReceiptUpdateVars($type, $swap_process, $overrides=null) {
        $receipt_update_vars = [
            'type'          => $type,

            'quantityIn'    => $swap_process['in_quantity'],
            'assetIn'       => $swap_process['in_asset'],
            'txidIn'        => $swap_process['transaction']['txid'],

            'quantityOut'   => $swap_process['quantity'],
            'assetOut'      => $swap_process['asset'],

            'confirmations' => $swap_process['confirmations'],
            'destination'   => $swap_process['destination'],
        ];

        if ($overrides !== null) { $receipt_update_vars = array_merge($receipt_update_vars, $overrides); }
        return $receipt_update_vars;
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

            // mark details
            $receipt_update_vars = [
                'type'          => 'pending',

                'quantityIn'    => $swap_process['in_quantity'],
                'assetIn'       => $swap_process['in_asset'],
                'txidIn'        => $swap_process['transaction']['txid'],

                'quantityOut'   => $swap_process['quantity'],
                'assetOut'      => $swap_process['asset'],

                'confirmations' => $swap_process['confirmations'],
                'destination'   => $swap_process['destination'],

                'timestamp'     => time(),
            ];

            $swap_process['swap_update_vars']['receipt'] = $receipt_update_vars;

            // log as confirming
            $swap_update_vars = ['state' => SwapState::CONFIRMING];
            $this->bot_event_logger->logConfirmingSwap($swap_process['bot'], $swap_process['swap'], $receipt_update_vars, $swap_update_vars);
        } else if ($swap_process['confirmations'] >= $swap_process['bot']['confirmations_required']) {
            if ($swap_process['swap']->isConfirming()) {

                // mark details
                $receipt_update_vars = [
                    'type'          => 'pending',

                    'quantityIn'    => $swap_process['in_quantity'],
                    'assetIn'       => $swap_process['in_asset'],
                    'txidIn'        => $swap_process['transaction']['txid'],

                    'quantityOut'   => $swap_process['quantity'],
                    'assetOut'      => $swap_process['asset'],

                    'confirmations' => $swap_process['confirmations'],
                    'destination'   => $swap_process['destination'],

                    'timestamp'     => time(),
                ];
                $swap_process['swap_update_vars']['receipt'] = $receipt_update_vars;

                // log the confirmed swap
                $swap_update_vars = ['state' => SwapState::READY];
                $this->bot_event_logger->logConfirmedSwap($swap_process['bot'], $swap_process['swap'], $receipt_update_vars, $swap_update_vars);

                // the swap just became confirmed
                //   update the state right now
                $swap_process['swap']->stateMachine()->triggerEvent(SwapStateEvent::CONFIRMED);

            }
        }

    }
    
    protected function doSwap($swap_process) {
        if ($swap_process['swap_was_handled']) { return; }

        if (!$swap_process['swap']->isReady()) {
            $this->bot_event_logger->logSwapNotReady($swap_process['bot'], $swap_process['swap']);
            return;
        }

        // do we need to refund
        $is_refunding = $swap_process['swap_config']->getStrategy()->shouldRefundTransaction($swap_process['swap_config'], $swap_process['in_quantity']);
        if ($is_refunding) {
            // refund
            $this->doRefund($swap_process);
        } else {
            // do forward swap
            $this->doForwardSwap($swap_process);
        }
    }

    protected function doForwardSwap($swap_process) {
        // log the attempt to send
        $this->bot_event_logger->logSendAttempt($swap_process['bot'], $swap_process['swap'], $swap_process['xchain_notification'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset'], $swap_process['confirmations']);

        // update the swap receipts (before the attempt)
        $receipt_update_vars = [
            'type'             => 'swap',

            'quantityIn'       => $swap_process['in_quantity'],
            'assetIn'          => $swap_process['in_asset'],
            'txidIn'           => $swap_process['transaction']['txid'],
            'confirmations'    => $swap_process['confirmations'],

            'quantityOut'      => $swap_process['quantity'],
            'assetOut'         => $swap_process['asset'],
            'confirmationsOut' => 0,

            'destination'      => $swap_process['destination'],

            'completedAt'      => time(),
            'timestamp'        => time(),
        ];
        $swap_process['swap_update_vars']['receipt'] = $receipt_update_vars;

        // send it
        try {
            $send_result = $this->sendAssets($swap_process['bot'], $swap_process['destination'], $swap_process['quantity'], $swap_process['asset']);
        } catch (Exception $e) {
            // move the swap into an error state
            $swap_process['state_trigger'] = SwapStateEvent::SWAP_ERRORED;

            throw $e;
        }


        // update the txidOut
        $receipt_update_vars['txidOut'] = $send_result['txid'];
        $swap_process['swap_update_vars']['receipt'] = $receipt_update_vars;
        $swap_process['swap_update_vars']['completed_at'] = Carbon::now();


        // update the local balance
        $swap_process['bot_balance_deltas'] = $this->updateBalanceDeltasFromProcessedSwap($swap_process, $swap_process['bot_balance_deltas'], $receipt_update_vars['quantityOut'], $receipt_update_vars['assetOut']);

        // move the swap into the sent state
        $swap_process['state_trigger'] = SwapStateEvent::SWAP_SENT;

        // log it
        $swap_update_vars = ['state' => SwapState::SENT];
        $this->bot_event_logger->logSwapSent($swap_process['bot'], $swap_process['swap'], $receipt_update_vars, $swap_update_vars);

        // mark the swap as sent
        $swap_process['swap_was_sent'] = true;
    }

    protected function doRefund($swap_process) {
        // send the refund
        try {
            list($out_quantity, $out_asset, $fee, $dust_size) = $this->buildRefundDetails($swap_process['bot'], $swap_process['xchain_notification']);

            // build trial receipt vars
            $receipt_update_vars = [
                'type'             => 'refund',

                'quantityIn'       => $swap_process['in_quantity'],
                'assetIn'          => $swap_process['in_asset'],
                'txidIn'           => $swap_process['transaction']['txid'],

                'quantityOut'      => $out_quantity,
                'assetOut'         => $out_asset,
                'confirmationsOut' => 0,

                'confirmations'    => $swap_process['confirmations'],
                'destination'      => $swap_process['destination'],

                'completedAt'      => time(),
                'timestamp'        => time(),
            ];

            // log the attempt to refund
            $this->bot_event_logger->logRefundAttempt($swap_process['bot'], $swap_process['swap'], $receipt_update_vars);

            if ($out_quantity > 0) {
                // do the send
                $send_result = $this->sendAssets($swap_process['bot'], $swap_process['destination'], $out_quantity, $out_asset, $fee, $dust_size);
            } else {
                // return quantity was less than 0 - don't send any refund
                $send_result = ['txid' => null];
            }
        } catch (Exception $e) {
            // move the swap into an error state
            $swap_process['state_trigger'] = SwapStateEvent::SWAP_ERRORED;

            throw $e;
        }

        // update the swap receipt vars
        $receipt_update_vars['txidOut'] = $send_result['txid'];

        // update the swap
        $swap_process['swap_update_vars']['receipt'] = $receipt_update_vars;
        $swap_process['swap_update_vars']['completed_at'] = Carbon::now();

        // update the local balance
        $swap_process['bot_balance_deltas'] = $this->updateBalanceDeltasFromProcessedSwap($swap_process, $swap_process['bot_balance_deltas'], $out_quantity, $out_asset, $fee, $dust_size);

        // move the swap into the sent state
        $swap_process['state_trigger'] = SwapStateEvent::SWAP_REFUND;

        // log it
        $swap_update_vars = ['state' => SwapState::REFUNDED];
        $this->bot_event_logger->logSwapRefunded($swap_process['bot'], $swap_process['swap'], $receipt_update_vars, $swap_update_vars);

        // mark the swap as sent
        $swap_process['swap_was_sent'] = true;
    }

    protected function buildRefundDetails($bot, $xchain_notification) {
        if ($xchain_notification['asset'] == 'BTC') {
            // BTC Refund
            $fee = $bot['return_fee'];
            $dust_size = null;
            $out_quantity = $xchain_notification['quantity'] - $fee;
            $out_asset = $xchain_notification['asset'];

        } else {
            // Counterparty asset refund
            $input_dust_size_sat = $xchain_notification['counterpartyTx']['dustSizeSat'];
            $fee_sat = floor($input_dust_size_sat * 0.2);
            $dust_size_sat = $input_dust_size_sat - $fee_sat;

            $fee = CurrencyUtil::satoshisToValue($fee_sat);
            $dust_size = CurrencyUtil::satoshisToValue($dust_size_sat);
            $out_asset = $xchain_notification['asset'];
            $out_quantity = $xchain_notification['quantity'];
        }

        return [$out_quantity, $out_asset, $fee, $dust_size];
    }

    protected function updateBalanceDeltasFromProcessedSwap($swap_process, $bot_balance_deltas, $quantity, $asset, $btc_fee=null, $dust_size=null) {
        if ($btc_fee === null) { $btc_fee = $swap_process['bot']['return_fee']; }

        $bot_balance_deltas = $this->balance_updater->modifyBalanceDeltasForSend([], $asset, $quantity, $btc_fee, $dust_size);
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

    protected function sendAssets($bot, $destination, $quantity, $asset, $fee=null, $dust_size=null) {
        // call xchain
        if ($fee === null) { $fee = $bot['return_fee']; }
        $send_result = $this->xchain_client->send($bot['public_address_id'], $destination, $quantity, $asset, $fee, $dust_size);

        return $send_result;
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
