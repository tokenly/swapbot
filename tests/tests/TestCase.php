<?php

class TestCase extends Illuminate\Foundation\Testing\TestCase {

    protected $baseUrl = 'http://localhost';

	protected $use_database = false;

    public function setUp()
    {
        parent::setUp();

        if ($this->use_database) { $this->setUpDb(); }
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
        $this->app['Illuminate\Contracts\Console\Kernel']->call('migrate:reset');
    }

}
