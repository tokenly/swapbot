<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateTestSwapCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:create-test-swap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a Test Swap';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setHelp(<<<EOF
Creates a Test Swap by running a test scenario.  Do not do this on a live database.
EOF
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['erase', null, InputOption::VALUE_NONE, 'Erase Database'],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        // erase the database first if specified
        if ($this->input->getOption('erase')) {
            $this->comment("erasing database");
            app('Illuminate\Contracts\Console\Kernel')->call('migrate:reset');
            app('Illuminate\Contracts\Console\Kernel')->call('migrate');
            $this->comment("done");
        }

        // run scenario 44
        app('ScenarioRunner')->init(null)->runScenarioByNumber(44);
        foreach (app('Swapbot\Repositories\SwapRepository')->findAll() as $swap) {
            $this->info("Swap {$swap['uuid']} created");
        }
        $this->info('done');
    }


}
