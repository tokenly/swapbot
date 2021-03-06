<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;

class UpdateBotPaymentAccount extends Command {


    var $bot;
    var $amount;
    var $asset;
    var $is_credit;
    var $bot_event;
    var $btc_dust;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $amount, $asset, $is_credit, BotEvent $bot_event, $btc_dust=null)
    {
        $this->bot       = $bot;
        $this->amount    = $amount;
        $this->asset     = $asset;
        $this->is_credit = $is_credit;
        $this->bot_event = $bot_event;
        $this->btc_dust  = $btc_dust;
    }

}
