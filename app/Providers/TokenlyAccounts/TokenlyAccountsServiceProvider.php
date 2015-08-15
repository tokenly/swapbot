<?php

namespace Swapbot\Providers\TokenlyAccounts;

use Exception;
use Illuminate\Support\ServiceProvider;
use Swapbot\Providers\Accounts\AccountHandler;
use Swapbot\Providers\TokenlyAccounts\TokenlyAccountsSocialiteManager;

class TokenlyAccountsServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('Laravel\Socialite\Contracts\Factory', function ($app) {
            return new TokenlyAccountsSocialiteManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Laravel\Socialite\Contracts\Factory'];
    }

}
