<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;

class ReceiveBotPayment extends Command {


    var $bot;
    var $amount;
    var $bot_event;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $amount, BotEvent $bot_event)
    {
        $this->bot       = $bot;
        $this->amount    = $amount;
        $this->bot_event = $bot_event;
    }

}
