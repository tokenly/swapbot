<?php

namespace Swapbot\Swapbot;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Bot;
use Swapbot\Repositories\BlockRepository;
use Swapbot\Swap\Logger\Facade\BotEventLogger;
use Swapbot\Swap\Processor\SwapProcessor;
use Swapbot\Swap\Util\RequestIDGenerator;
use Tokenly\XChainClient\Client;

class ShutdownHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BlockRepository $block_repository, Client $xchain_client)
    {
        $this->block_repository = $block_repository;
        $this->xchain_client    = $xchain_client;
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

        // get the BTC balances
        $btc_balance = $bot['balances']['BTC'];

        foreach ($bot['balances'] as $asset => $quantity) {
            // save BTC to the end
            if ($asset == 'BTC') { continue; }

            if ($btc_balance < ($fee + $dust_size)) { throw new Exception("Not enough BTC to refund token $asset", 1); }

            // send to the shutdown address
            $request_id = RequestIDGenerator::generateSendHash(['shutdown', $bot['uuid']], $shutdown_address, $quantity, $asset);
            $results = $this->xchain_client->sendConfirmed($bot['public_address_id'], $shutdown_address, $quantity, $asset, $fee, null, $request_id);
            $txid = $results['txid'];

            // Log it
            BotEventLogger::logBotShutdownSend($bot, $shutdown_address, $quantity, $asset, $txid);

            // subtract the fee and the dust size
            $btc_balance = $btc_balance - $fee - $dust_size;
        }

        // send the btc last
        if (($btc_balance - $fee) > 0) {
            // send to the shutdown address
            $quantity = $btc_balance - $fee;
            Log::debug("\$quantity=".json_encode($quantity, 192));
            $request_id = RequestIDGenerator::generateSendHash(['shutdown', $bot['uuid']], $shutdown_address, $quantity, 'BTC');
            $results = $this->xchain_client->sendConfirmed($bot['public_address_id'], $shutdown_address, $quantity, 'BTC', $fee, null, $request_id);
            $txid = $results['txid'];

            // Log it
            BotEventLogger::logBotShutdownSend($bot, $shutdown_address, $quantity, 'BTC', $txid);
        }

        return true;
    }

}
