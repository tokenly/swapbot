<?php

namespace Swapbot\Swap\Logger\OutputTransformer\Facade;

use Illuminate\Support\Facades\Facade;

class BotEventOutputTransformer extends Facade {


    protected static function getFacadeAccessor() { return 'boteventlogoutput'; }


}
