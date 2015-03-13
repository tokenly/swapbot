<?php

namespace Swapbot\Console\Commands\Development;

use Carbon\Carbon;
use Fhaculty\Graph\Graph;
use Graphp\GraphViz\GraphViz;
use Illuminate\Console\Command;
use Metabor\Statemachine\Graph\GraphBuilder;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\SwapState;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExportSwapStateGraph extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:export-swap-state-graph';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Builds an SVG of the swap state machine.';

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
        $this->comment('exporting swap graph');

        $graph = new Graph();
        $builder = new GraphBuilder($graph);

        $process = app('Swapbot\Statemachines\SwapStateMachineFactory')->buildStateMachineProcess(SwapState::BRAND_NEW, "Swap State");

        $builder->addStateCollection($process);
        $viz = new GraphViz($graph);
        $viz->setFormat('svg');

        copy($viz->createImageFile($graph), 'swap-graph.'.Carbon::create()->format("Ymd_His").'.svg');
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
