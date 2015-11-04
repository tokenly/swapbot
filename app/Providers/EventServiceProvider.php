<?php namespace Swapbot\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {

	/**
	 * The event handler mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [
		'event.name' => [
			'EventListener',
		],
	];

	/**
	 * The subscriber classes to register.
	 *
	 * @var array
	 */
	protected $subscribe = [
		\Swapbot\Handlers\Events\BotUpdatesForDisplayHandler::class,
		\Swapbot\Handlers\Events\BotIndexHandler::class,
		\Swapbot\Handlers\Events\CustomerEmailHandler::class,
		\Swapbot\Handlers\Events\AdminEmailHandler::class,
		\Swapbot\Handlers\Events\KeenEventsHandler::class,
		\Swapbot\Handlers\Events\SlackEventsHandler::class,
		\Swapbot\Handlers\Events\WhitelistEventsHandler::class,
	];


	/**
	 * Register any other events for your application.
	 *
	 * @param  \Illuminate\Contracts\Events\Dispatcher  $events
	 * @return void
	 */
	public function boot(DispatcherContract $events)
	{
		parent::boot($events);

		//
	}

}
