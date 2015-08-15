<?php

namespace Swapbot\Providers\TokenlyAccounts;

use Laravel\Socialite\SocialiteManager;

class TokenlyAccountsSocialiteManager extends SocialiteManager
{

    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function createTokenlyAccountsDriver()
    {
        $config = $this->app['config']['services.tokenlyAccounts'];

        return $this->buildProvider(
            'Swapbot\Providers\TokenlyAccounts\Socialite\Two\TokenlyAccountsProvider', $config
        );
    }

    /**
     * Get the default driver name.
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return 'tokenlyAccounts';
    }

    /**
     * Build an OAuth 2 provider instance.
     *
     * @param  string  $provider
     * @param  array  $config
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    public function buildProvider($provider, $config)
    {
        $provider = new $provider(
            $this->app['request'], $config['client_id'],
            $config['client_secret'], $config['redirect']
        );

        $provider->setBaseURL($config['base_url']);

        return $provider;
    }

}
