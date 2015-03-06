<?php namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;
use Swapbot\Models\Transaction;

class BillBotForTransaction extends Command {

    var $bot;
    var $transaction;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, Transaction $transaction)
    {
        $this->bot         = $bot;
        $this->transaction = $transaction;
    }

}
