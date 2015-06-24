<?php

namespace Swapbot\Swap\Settings\Facade;

use Exception;
use Illuminate\Support\Facades\Facade;

/**
* Settings facade
*/
class Settings extends Facade {

    protected static function getFacadeAccessor() { return 'swapbotsettings'; }

}


