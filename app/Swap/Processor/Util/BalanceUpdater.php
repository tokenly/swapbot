<?php

namespace Swapbot\Swap\Processor\Util;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Swapbot\Repositories\BotRepository;

class BalanceUpdater {

    const DEFAULT_REGULAR_DUST_SIZE = 0.00005430;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository)
    {
        $this->bot_repository = $bot_repository;
    }

    public function modifyBalanceDeltasFromTransactionReceived($balance_deltas, $xchain_notification) {
        $asset    = $xchain_notification['asset'];
        $quantity = $xchain_notification['quantity'];


        // add the asset balance
        if (!isset($balance_deltas[$asset])) { $balance_deltas[$asset] = 0; }
        $balance_deltas[$asset] = $balance_deltas[$asset] + $quantity;

        // add the BTC amount
        if ($asset != 'BTC') {
            $btc_amount = $xchain_notification['counterpartyTx']['dustSize']; //
            
            if (!isset($balance_deltas['BTC'])) { $balance_deltas['BTC'] = 0; }
            $balance_deltas['BTC'] = $balance_deltas['BTC'] + $btc_amount;
        }

        return $balance_deltas;
    }


    public function modifyBalanceDeltasForSend($balance_deltas, $asset, $quantity, $btc_fee=null, $dust_size=null) {
        if ($btc_fee === null) { $btc_fee = Config::get('swapbot.defaultFee'); }

        // subtract the asset balance
        if (!isset($balance_deltas[$asset])) { $balance_deltas[$asset] = 0; }
        $balance_deltas[$asset] = $balance_deltas[$asset] - $quantity;

        // subtract the dust
        if ($asset != 'BTC') {
            if ($dust_size === null) { $dust_size = self::DEFAULT_REGULAR_DUST_SIZE; }
            if (!isset($balance_deltas['BTC'])) { $balance_deltas['BTC'] = 0; }
            $balance_deltas['BTC'] = $balance_deltas['BTC'] - $dust_size;
        }

        // subtract the BTC fee
        if (!isset($balance_deltas['BTC'])) { $balance_deltas['BTC'] = 0; }
        $balance_deltas['BTC'] = $balance_deltas['BTC'] - $btc_fee;

        return $balance_deltas;
    }


    public function updateBotBalances($bot, $balance_deltas) {
        if ($balance_deltas) {
            DB::transaction(function() use ($bot, $balance_deltas) {
                $this->bot_repository->executeWithLockedBot($bot, function($locked_bot) use ($balance_deltas, &$balances_in_memory) {
                    $balances = $locked_bot['balances'];

                    // build the new balances
                    foreach($balance_deltas as $asset => $balance_delta) {
                        if (!isset($balances[$asset])) { $balances[$asset] = 0; }
                        $balances[$asset] = $balances[$asset] + $balance_delta;
                    }

                    // update the bot in the DB
                    $this->bot_repository->update($locked_bot, ['balances' => $balances]);
                });
            });
        }
    }

}
