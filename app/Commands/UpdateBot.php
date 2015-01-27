<?php namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;

class UpdateBot extends Command {

    var $bot;
    var $attributes;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(Bot $bot, $attributes)
	{
        $this->bot        = $bot;
        $this->attributes = $attributes;
	}

}
