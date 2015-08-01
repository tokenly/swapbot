<?php

namespace Swapbot\Providers\Accounts;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Swap;
use Swapbot\Models\Transaction;
use Swapbot\Swap\Logger\Facade\BotEventLogger;
use Swapbot\Swap\Processor\SwapProcessor;
use Swapbot\Swap\Processor\Util\BalanceUpdater;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\Client;
use Tokenly\XChainClient\Exception\XChainException;

class AccountHandler {

    function __construct(Client $xchain_client, BalanceUpdater $balance_updater) {
        $this->xchain_client   = $xchain_client;
        $this->balance_updater = $balance_updater;
    }

    public function swapAccountName(Swap $swap) {
        return 'swap-'.$swap['uuid'];
    }

    public function moveIncomingReceivedFunds(Swap $swap) {
        $this->xchainClientCall(function($xchain) use ($swap) {
            $transaction = $swap->transaction;
            $bot         = $swap->bot;
            $txid        = $transaction['txid'];
            // Log::debug("allocateNewSwapAccount calling transferAllByTransactionID for {$bot['public_address_id']} on swap {$swap['uuid']}");

            $success = $xchain->transferAllByTransactionID($bot['public_address_id'], 'default', $this->swapAccountName($swap), $txid);
            BotEventLogger::logTransferIncome($bot, $swap, $txid, 'default', $this->swapAccountName($swap));
            return $success;
        });
    }

    // returns false if the stock was not allocated
    //   transfers all or nothing for what the swap wants
    public function allocateStock(Swap $swap) {
        $account_name = $this->swapAccountName($swap);
        $bot = $swap->bot;

        $actual_balances = $this->xchainClientCall(function($xchain) use ($swap, $bot, $account_name) {
            return $xchain->getAccountBalances($bot['public_address_id'], $account_name, 'confirmed');
        });

        $default_balances = $this->xchainClientCall(function($xchain) use ($swap, $bot, $account_name) {
            return $xchain->getAccountBalances($bot['public_address_id'], 'default', 'confirmed');
        });

        $all_succeeded = true;
        $balances_desired = $this->buildOutputBalancesNeeded($swap);
        foreach($balances_desired as $asset => $desired_quantity) {
            $actual_quantity = isset($actual_balances[$asset]) ? $actual_balances[$asset] : 0;
            $needed_quantity = $desired_quantity - $actual_quantity;
            if ($needed_quantity > 0) {
                $default_quantity = isset($default_balances[$asset]) ? $default_balances[$asset] : 0;
                if ($default_quantity < $needed_quantity) {
                    // swapbot is reporting that it does not have enough stock
                    $transferred_successfully = false;
                } else {
                    // stock looks good
                    // transfer from the default to the swap account
                    $transferred_successfully = $this->xchainClientCall(function($xchain) use ($swap, $bot, $account_name, $needed_quantity, $asset) {
                        return $xchain->transfer($bot['public_address_id'], 'default', $account_name, $needed_quantity, $asset);
                    });

                    if ($transferred_successfully !== false) {
                        BotEventLogger::logTransferInventory($bot, $swap, $needed_quantity, $asset, 'default', $account_name);

                        // update the bot with the new balances
                        $this->balance_updater->syncBalances($bot);
                    } else {
                        BotEventLogger::logTransferInventoryFailed($bot, $swap, $needed_quantity, $asset, 'default', $account_name);
                    }
                }

                if ($transferred_successfully === false) { $all_succeeded = false; }
            }
        }

        return $all_succeeded;
    }


    // closes the swap account and transfers all funds back to the default account
    public function closeSwapAccount(Swap $swap) {
        $account_name = $this->swapAccountName($swap);
        $bot = $swap->bot;

        $closed_actual_balances = $this->xchainClientCall(function($xchain) use ($swap, $bot, $account_name) {
            return $xchain->getAccountBalances($bot['public_address_id'], $account_name);
        });

        $account_closed = $this->xchainClientCall(function($xchain) use ($swap, $bot, $account_name) {
            return $xchain->closeAccount($bot['public_address_id'], $account_name, 'default');
        });

        if ($account_closed) {
            BotEventLogger::logAccountClosed($bot, $swap, $closed_actual_balances);
        } else {
            BotEventLogger::logAccountClosedFailed($bot, $swap, $closed_actual_balances);
        }

        return $account_closed;
    }


    ////////////////////////////////////////////////////////////////////////

    protected function xchainClientCall($function) {
        try {
            return $function($this->xchain_client);
        } catch (XChainException $e) {
            EventLog::logError('xchain.call.failed', $e, ['errorName' => $e->getErrorName()]);
            throw $e;
        } catch (Exception $e) {
            EventLog::logError('xchain.call.failed', $e);
            throw $e;
        }
    }

    protected function buildOutputBalancesNeeded($swap) {
        $desired_quantity = $swap['receipt']['quantityOut'];
        $desired_asset    = $swap['receipt']['assetOut'];
        $balances = [$desired_asset => $desired_quantity];

        // add dust and change to non BTC purchases
        if ($desired_asset != 'BTC') {
            $change_out = isset($swap['receipt']['changeOut']) ? $swap['receipt']['changeOut'] : 0;
            $dust_size = SwapProcessor::DEFAULT_REGULAR_DUST_SIZE;
            $balances['BTC'] = $change_out + $dust_size;
        }

        // add fee to all purchases
        $bot = $swap->bot;
        $balances['BTC'] += $bot['return_fee'];

        return $balances;
    }

    protected function anyBalancesExist($actual_balances) {

    }

}
