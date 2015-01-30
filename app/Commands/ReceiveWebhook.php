<?php namespace Swapbot\Commands;

use Swapbot\Commands\Command;

class ReceiveWebhook extends Command {

    var $payload;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

}
