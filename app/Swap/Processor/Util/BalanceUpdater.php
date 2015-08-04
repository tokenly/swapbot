<?php

namespace Swapbot\Swap\Processor\Util;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Bot;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\Logger\Facade\BotEventLogger;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\Client;

class BalanceUpdater {

    const XCHAIN_INCOMING_CONFIRMATIONS_REQUIRED = 2;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, Client $xchain_client)
    {
        $this->bot_repository = $bot_repository;
        $this->xchain_client  = $xchain_client;
    }

    // returns the confirmed balances
    public function syncBalances(Bot $bot) {
        try {
            $all_balances = $this->xchain_client->getAccountBalances($bot['public_address_id'], 'default');
            $this->bot_repository->update($bot, [
                'balances'             => $all_balances['confirmed'],
                'all_balances_by_type' => $all_balances,
                'balances_updated_at'  => Carbon::now(),
            ]);

            BotEventLogger::logBotBalancesSynced($bot, $all_balances);

            return $all_balances['confirmed'];
        } catch (Exception $e) {
            EventLog::logError('bot.balancesSyncFailed', $e, ['bot' => $bot['id'], 'name' => $bot['name']]);
            BotEventLogger::logBotBalancesSyncFailed($bot);
            return [];
        }
    }


}
