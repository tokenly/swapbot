<?php namespace Swapbot\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		'Swapbot\Console\Commands\Bot\ActivateBotCommand',
		'Swapbot\Console\Commands\Bot\ListAllBotsCommand',
		'Swapbot\Console\Commands\Bot\ReconcileBotStateCommand',
		'Swapbot\Console\Commands\Bot\UpdateBotBalancesCommand',
		'Swapbot\Console\Commands\Bot\ChangeBotStateCommand',
		'Swapbot\Console\Commands\Bot\SweepBotCommand',
		'Swapbot\Console\Commands\Bot\DeleteBotCommand',
		'Swapbot\Console\Commands\Bot\ListBotSwapsCommand',
		'Swapbot\Console\Commands\Bot\ForwardPaymentCommand',
		'Swapbot\Console\Commands\Bot\ProcessIncomeForwardingForAllBotsCommand',

		'Swapbot\Console\Commands\Swap\DeleteSwapCommand',
		'Swapbot\Console\Commands\Swap\ResetSwapCommand',
		'Swapbot\Console\Commands\Swap\ReconcileSwapStatesCommand',
		
		'Swapbot\Console\Commands\Compile\CompileEventsCommand',

		'Swapbot\Console\Commands\Development\ExportBotStateGraph',
		'Swapbot\Console\Commands\Development\ExportSwapStateGraph',
		'Swapbot\Console\Commands\Development\UpdateBotPaymentAccount',

		'Swapbot\Console\Commands\Development\CreateTestSwapCommand',
		'Swapbot\Console\Commands\Development\TestCreateBotBalancesUpdateCommand',
		'Swapbot\Console\Commands\Development\TestPushBotEventCommand',
		'Swapbot\Console\Commands\Development\TestReceiveXChainNotificationCommand',
		'Swapbot\Console\Commands\Development\TestReceiveFromXChainTemplateCommand',
		'Swapbot\Console\Commands\Development\PopulateMissingSwapReceiptsCommand',
		'Swapbot\Console\Commands\Development\ResetBotHistoryCommand',
		'Swapbot\Console\Commands\Development\UpgradeBotToMonthlyCommand',
		'Swapbot\Console\Commands\Development\CreateInitialPoolAddressCommand',
		'Swapbot\Console\Commands\Development\ProcessPendingSwapCommand',
		'Swapbot\Console\Commands\Development\TestRenderBotEventCommand',
		'Swapbot\Console\Commands\Development\IndexBotsCommand',
		'Swapbot\Console\Commands\Development\SendKeenTestEventCommand',
		'Swapbot\Console\Commands\Development\SendSlackTestEventCommand',
		'Swapbot\Console\Commands\Development\SendKeenSwapbotEvents',
		'Swapbot\Console\Commands\Development\CreateMissingURLSlugsCommand',

		// vendor commands
		'Tokenly\ConsulHealthDaemon\Console\ConsulHealthMonitorCommand',
		'Tokenly\QuotebotClient\Console\PopulateQuotesCommand',
		'Tokenly\QuotebotClient\Console\GetQuoteCommand',
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('inspire')
				 ->hourly();
	}

}
