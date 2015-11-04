<?php

namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Whitelist;

class WhitelistWasDeleted extends Event
{

    var $whitelist;

    public function __construct(Whitelist $whitelist)
    {
        $this->whitelist = $whitelist;
    }


}
