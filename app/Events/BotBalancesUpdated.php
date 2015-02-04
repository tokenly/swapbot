<?php namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Bot;

class BotBalancesUpdated extends Event {

    var $bot;
    var $old_balances;
    var $new_balances;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $old_balances, $new_balances)
    {
        $this->bot          = $bot;
        $this->old_balances = $old_balances;
        $this->new_balances = $new_balances;
    }

}
