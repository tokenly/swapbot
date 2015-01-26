<?php namespace Swapbot\Commands;

use Swapbot\Commands\Command;

class CreateBot extends Command {

    var $params;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct($params)
	{
		$this->params = $params;
	}

}
