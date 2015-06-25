<?php

namespace Swapbot\Swap\DateProvider\Provider;

use Exception;
use Illuminate\Support\ServiceProvider;

class DateProviderServiceProvider extends ServiceProvider {

    public function register() {

        $this->app->bind('dateprovider', function($app) {
            return app('Swapbot\Swap\DateProvider\DateProvider');
        });

    }



    

}
