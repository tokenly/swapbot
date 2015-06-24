<?php

namespace Swapbot\Swap\Settings\Provider;

use Exception;
use Illuminate\Support\ServiceProvider;

class BotSettingsServiceProvider extends ServiceProvider {

    public function register() {

        $this->app->bind('swapbotsettings', function($app) {
            return app('Swapbot\Swap\Settings\Settings');
        });

    }



    

}
