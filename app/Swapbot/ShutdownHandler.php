<?php

namespace Swapbot\Swapbot;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Bot;
use Swapbot\Repositories\BlockRepository;
use Swapbot\Swap\Logger\Facade\BotEventLogger;
use Swapbot\Swap\Processor\SwapProcessor;
use Swapbot\Swap\Processor\Util\BalanceUpdater;
use Swapbot\Swap\Util\RequestIDGenerator;
use Tokenly\XChainClient\Client;

class ShutdownHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BlockRepository $block_repository, Client $xchain_client, BalanceUpdater $balance_updater)
    {
        $this->block_repository = $block_repository;
        $this->xchain_client    = $xchain_client;
        $this->balance_updater  = $balance_updater;
    }

    /**
     * Handle the command.
     *
     * @param  ReconcileBotState  $command
     * @return void
     */
    public function botHasPassedShutdownBlock(Bot $bot) {
        if (!$bot['shutdown_block']) { return false; }

        if ($bot['shutdown_block'] <= $this->block_repository->findBestBlockHeight()) {
            return true;
        }

        return false;
    }

    public function refundBot(Bot $bot) {
        $shutdown_address = $bot['shutdown_address'];
        $fee = Config::get('swapbot.defaultFee');
        $dust_size = SwapProcessor::DEFAULT_REGULAR_DUST_SIZE;


        // check for open accounts
        $all_accounts = $this->xchain_client->getAccounts($bot['public_address_id']);
        Log::debug("\$all_accounts=".json_encode($all_accounts, 192));
        if (count($all_accounts) > 1) {
            // an account is still open
            BotEventLogger::logBotShutdownDelayed($bot);
            return false;
        }

        // sync the bot balances
        $this->balance_updater->syncBalances($bot);


        // get the BTC balances
        $btc_balance = $bot['balances']['BTC'];

        foreach ($bot['balances'] as $asset => $quantity) {
            // save BTC to the end
            if ($asset == 'BTC') { continue; }
            if ($quantity <= 0) { continue; }

            // get the recommended fee
            $fee = $this->getRecommendedFee($bot, $btc_balance, $shutdown_address, $quantity, $asset);
            if ($fee < Config::get('swapbot.defaultFee')) {
                $fee = Config::get('swapbot.defaultFee');
            }
            // Log::debug("getRecommendedFee \$fee=".json_encode($fee, 192));

            if ($fee === null OR $btc_balance < ($fee + $dust_size)) { throw new Exception("Not enough BTC to refund token $asset", 1); }

            // send to the shutdown address
            $request_id = RequestIDGenerator::generateSendHash(['shutdown', $bot['uuid']], $shutdown_address, $quantity, $asset);
            $results = $this->xchain_client->sendConfirmed($bot['public_address_id'], $shutdown_address, $quantity, $asset, $fee, null, $request_id);
            $txid = $results['txid'];

            // Log it
            BotEventLogger::logBotShutdownSend($bot, $shutdown_address, $quantity, $asset, $txid);

            // update balances
            $this->balance_updater->syncBalances($bot);

            // subtract the fee and the dust size
            $btc_balance = $btc_balance - $fee - $dust_size;
        }

        // send the btc last
        $btc_quantity_to_estimate = $btc_balance;
        $fee = $this->getRecommendedFee($bot, $btc_balance, $shutdown_address, $btc_quantity_to_estimate, 'BTC');
        // Log::debug("getRecommendedFee \$fee=".json_encode($fee, 192));
        if (($btc_balance - $fee) > 0) {
            // send to the shutdown address
            $btc_quantity_to_send = $btc_balance - $fee;
            Log::debug("\$btc_quantity_to_send=".json_encode($btc_quantity_to_send, 192));
            $request_id = RequestIDGenerator::generateSendHash(['shutdown', $bot['uuid']], $shutdown_address, $btc_quantity_to_send, 'BTC');
            $results = $this->xchain_client->sendConfirmed($bot['public_address_id'], $shutdown_address, $btc_quantity_to_send, 'BTC', $fee, null, $request_id);
            $txid = $results['txid'];

            // Log it
            BotEventLogger::logBotShutdownSend($bot, $shutdown_address, $btc_quantity_to_send, 'BTC', $txid);

            // update balances
            $this->balance_updater->syncBalances($bot);
        }

        return true;
    }

    protected function getRecommendedFee(Bot $bot, $btc_balance, $shutdown_address, $quantity, $asset) {
        $dust_size = SwapProcessor::DEFAULT_REGULAR_DUST_SIZE;

        // get the recommended fee
        foreach (['med', 'low'] as $priority) {
            $recommended_fee = $this->xchain_client->estimateFee($priority, $bot['public_address_id'], $shutdown_address, $quantity, $asset);
            $fee_float = $recommended_fee->getFloat();
            if ($btc_balance >= $fee_float + $dust_size) {
                return $fee_float;
            }
        }

        return null;
    }

}
