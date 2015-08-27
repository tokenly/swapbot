<?php

namespace Swapbot\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Swapbot\Events\Event;
use Swapbot\Models\Bot;

class BotPaymentStateBecameSoon extends Event
{

    var $bot;

    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
    }
}

