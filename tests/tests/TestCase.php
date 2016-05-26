<?php

class TestCase extends Illuminate\Foundation\Testing\TestCase {

    protected $baseUrl = 'http://localhost';

	protected $use_database = false;

    public function setUp()
    {
        parent::setUp();

        if ($this->use_database) { $this->setUpDb(); }

        // mock tokenpass api by default
        app('TokenpassHelper')->mockTokenpassAPI();
    }


	/**
	 * Creates the application.
	 *
	 * @return \Illuminate\Foundation\Application
	 */
	public function createApplication()
	{
		$app = require __DIR__.'/../../bootstrap/app.php';

		$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

		return $app;
	}

    public function setUpDb()
    {
        $this->app['Illuminate\Contracts\Console\Kernel']->call('migrate');
    }

    public function teardownDb()
    {
        // $this->app['Illuminate\Contracts\Console\Kernel']->call('migrate:reset');

        \Swapbot\Models\Bot::truncate();
        \Swapbot\Models\BotEvent::truncate();
        \Swapbot\Models\BotLeaseEntry::truncate();
        \Swapbot\Models\BotLedgerEntry::truncate();
        \Swapbot\Models\Customer::truncate();
        \Swapbot\Models\Image::truncate();
        \Swapbot\Models\NotificationReceipt::truncate();
        \Swapbot\Models\Setting::truncate();
        \Swapbot\Models\Swap::truncate();
        \Swapbot\Models\Transaction::truncate();
        \Swapbot\Models\User::truncate();
    }

}
