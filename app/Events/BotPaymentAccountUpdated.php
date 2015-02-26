<?php

namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Bot;

class BotPaymentAccountUpdated extends Event {

	var $bot;
	var $amount;
	
	/**
	 * Create a new event instance.
	 *
	 * @return void
	 */
	public function __construct(Bot $bot, $amount)
	{
		$this->bot = $bot;
		$this->amount = $amount;
	}

}
