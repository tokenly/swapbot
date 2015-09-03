<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Bot;
use LinusU\Bitcoin\AddressValidator;
use Exception;

class ShutdownBot extends Command
{

    var $bot;
    var $shutdown_address;
    var $shutdown_delay;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $shutdown_address, $shutdown_delay=6)
    {
        if (!AddressValidator::isValid($shutdown_address)) { throw new Exception("Shutdown address $shutdown_address is not a valid bitcoin address", 1); }


        $this->bot              = $bot;
        $this->shutdown_address = $shutdown_address;
        $this->shutdown_delay   = $shutdown_delay;
    }

}
