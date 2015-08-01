<?php

namespace Swapbot\Swap\Logger\Provider;

use Exception;
use Illuminate\Support\ServiceProvider;
use Swapbot\Swap\Logger\BotEventLogger;

class BotEventLoggerServiceProvider extends ServiceProvider {

    public function register() {
        $this->app->bind('boteventlogger', function($app) {
            return new BotEventLogger(app('Swapbot\Repositories\BotEventRepository'), app('Swapbot\Repositories\BotRepository'));
        });

    }



    

}
