<?php

namespace Swapbot\Swap\Lock\Provider;

use Exception;
use Illuminate\Support\ServiceProvider;

class RecordLockServiceProvider extends ServiceProvider {

    public function register() {

        $this->app->bind('recordlock', function($app) {
            return app('Swapbot\Swap\Lock\RecordLock');
        });

    }



    

}
