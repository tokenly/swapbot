<?php namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Bot;

class BotBalancesUpdated extends Event {

    var $bot;
    var $balances;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $balances)
    {
        $this->bot      = $bot;
        $this->balances = $balances;
    }

}
