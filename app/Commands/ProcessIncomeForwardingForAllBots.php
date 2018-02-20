<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;

class ProcessIncomeForwardingForAllBots extends Command
{

    public $override_delay;
    public $limit_to_bot_id;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($override_delay = false, $limit_to_bot_id = null)
    {
        $this->override_delay = $override_delay;
        $this->limit_to_bot_id = $limit_to_bot_id;
    }

}
