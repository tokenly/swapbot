<?php

namespace Swapbot\Commands;

use LinusU\Bitcoin\AddressValidator;
use Swapbot\Commands\Command;
use Swapbot\Models\Bot;
use Exception;

class ForwardPayment extends Command
{
    var $bot;
    var $destination;
    var $asset;
    var $quantity;
    var $request_id;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot, $destination, $quantity, $asset, $request_id=null)
    {
        if (!AddressValidator::isValid($destination)) { throw new Exception("Destination $destination is not a valid bitcoin address", 1); }

        $this->bot         = $bot;
        $this->destination = $destination;
        $this->asset       = $asset;
        $this->quantity    = $quantity;
        $this->request_id  = $request_id;

    }

}