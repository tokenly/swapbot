<?php

namespace Swapbot\Console\Commands\Stats;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Swapbot\Swap\Logger\BotEventLogger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AggregateStatsCommand extends Command {


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:aggregate-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build stats';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['stats-type', InputArgument::REQUIRED, 'Stat type (payments,swaps)'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            // ['limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of items to archive.', null],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $stats_type = $this->argument('stats-type');
        // $this->comment('Building stats for '.$stats_type);

        $method = "buildStats_${stats_type}";
        $stat_lines = app('Swapbot\Swap\Stats\SwapStatsAggregator')->$method();

        $stdout = fopen('php://stdout', 'w');
        foreach($stat_lines as $offset => $stat_line) {
            if ($offset === 0) {
                fputcsv($stdout, array_keys($stat_line));
            }

            fputcsv($stdout, array_values($stat_line));
        }
        fclose($stdout);


        // $this->comment('Done.');
    }

}
