<?php

namespace Swapbot\Swap\Processor\Util;

use Exception;
use Illuminate\Support\Facades\DB;
use Swapbot\Repositories\BotRepository;

class BalanceUpdater {

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
            $btc_amount = 0; // need to calculate BTC dust here
            if (!isset($balance_deltas['BTC'])) { $balance_deltas['BTC'] = 0; }
            $balance_deltas['BTC'] = $balance_deltas['BTC'] + $btc_amount;
        }

        return $balance_deltas;
    }


    public function updateBotBalances($bot, $balance_deltas) {
        if ($balance_deltas) {
            DB::transaction(function() use ($bot, $balance_deltas) {

                $balances_in_memory = $bot['balances'];

                $locked_bot = $this->bot_repository->findByIDWithLock($bot['id']);
                $balances = $locked_bot['balances'];

                // build the new balances
                foreach($balance_deltas as $asset => $balance_delta) {
                    if (!isset($balances_in_memory[$asset])) { $balances_in_memory[$asset] = 0; }
                    $balances_in_memory[$asset] = $balances_in_memory[$asset] + $balance_delta;

                    if (!isset($balances[$asset])) { $balances[$asset] = 0; }
                    $balances[$asset] = $balances[$asset] + $balance_delta;
                }

                // update the bot in the DB
                $this->bot_repository->update($locked_bot, ['balances' => $balances]);

                // also update the bot in memory
                $bot['balances'] = $balances_in_memory;
            });
        }
    }

        // $btc_fee = $bot['return_fee'];


}
