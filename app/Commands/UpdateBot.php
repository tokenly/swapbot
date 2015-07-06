<?php namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;
use Swapbot\Models\User;

class UpdateBot extends Command {

    var $bot;
    var $attributes;
    var $user;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(Bot $bot, $attributes, User $user)
	{
        $this->bot        = $bot;
        $this->attributes = $attributes;
        $this->user       = $user;
	}

}
