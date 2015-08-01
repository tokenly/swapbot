<?php

namespace Swapbot\Providers\Accounts\Provider;

use Exception;
use Illuminate\Support\ServiceProvider;
use Swapbot\Providers\Accounts\AccountHandler;

class AccountHandlerServiceProvider extends ServiceProvider {

    public function register() {
        $this->app->bind('accounthandler', function($app) {
            return new AccountHandler(app('Tokenly\XChainClient\Client'), app('Swapbot\Swap\Processor\Util\BalanceUpdater'));
        });

    }



    

}
