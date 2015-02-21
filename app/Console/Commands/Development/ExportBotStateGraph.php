<?php

namespace Swapbot\Console\Commands\Development;

use Carbon\Carbon;
use Fhaculty\Graph\Graph;
use Graphp\GraphViz\GraphViz;
use Illuminate\Console\Command;
use Metabor\Statemachine\Graph\GraphBuilder;
use Swapbot\Models\Data\BotState;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExportBotStateGraph extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:export-bot-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Builds an SVG of the bot state.';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->comment('exporting bot graph');

        $graph = new Graph();
        $builder = new GraphBuilder($graph);

        $process = app('Swapbot\Statemachines\BotStateMachineFactory')->buildStateMachineProcess(BotState::BRAND_NEW);

        $builder->addStateCollection($process);
        $viz = new GraphViz($graph);
        $viz->setFormat('svg');

        copy($viz->createImageFile($graph), 'bot-graph.'.Carbon::create()->format("Ymd_His").'.svg');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            // ['example', InputArgument::REQUIRED, 'An example argument.'],
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
            // ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }

}
