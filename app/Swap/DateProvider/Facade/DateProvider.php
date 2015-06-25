<?php

namespace Swapbot\Swap\DateProvider\Facade;

use Exception;
use Illuminate\Support\Facades\Facade;

/**
* DateProvider facade
*/
class DateProvider extends Facade {

    protected static function getFacadeAccessor() { return 'dateprovider'; }

}


