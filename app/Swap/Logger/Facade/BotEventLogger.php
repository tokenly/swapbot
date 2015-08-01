<?php

namespace Swapbot\Swap\Logger\Facade;

use Illuminate\Support\Facades\Facade;

class BotEventLogger extends Facade {


    protected static function getFacadeAccessor() { return 'boteventlogger'; }


}
