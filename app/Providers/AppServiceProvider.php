<?php namespace Swapbot\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Swapbot\Swap\Stats\SwapStatsAggregator;

class AppServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		// make all forms use https
		if (env('USE_SSL', false)) {
		    URL::forceSchema('https');
		}
	}

	/**
	 * Register any application services.
	 *
	 * This service provider is a great spot to register your various container
	 * bindings with the application. As you can see, we are registering our
	 * "Registrar" implementation here. You can add your own bindings too!
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind(
			'Illuminate\Contracts\Auth\Registrar',
			'Swapbot\Services\Registrar'
		);

        $this->app->bind('Swapbot\Swap\Stats\SwapStatsAggregator', function($app) {
            return new SwapStatsAggregator(
            	$app->make('Swapbot\Repositories\BotEventRepository'),
            	$app->make('Swapbot\Repositories\BotRepository')
            );
        });

	}

}
