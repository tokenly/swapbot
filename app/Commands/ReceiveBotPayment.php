<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;

class ReceiveBotPayment extends Command {


    var $bot;
    var $amount;
    var $is_credit;
    var $bot_event;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $amount, $is_credit, BotEvent $bot_event)
    {
        $this->bot       = $bot;
        $this->amount    = $amount;
        $this->is_credit = $is_credit;
        $this->bot_event = $bot_event;
    }

}
