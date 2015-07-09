<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\ProcessPendingSwap;
use Swapbot\Commands\ProcessPendingSwaps;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\Logger\BotEventLogger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ProcessPendingSwapCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:process-pending-swap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a Pending Swap or all swaps.';

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
            ['swap-id', InputArgument::OPTIONAL, 'A swap ID to process a single swap.'],
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
            ['all', 'a', InputOption::VALUE_NONE, 'Process all pending swaps.'],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {
            if ($this->input->getOption('all')) {
                $this->comment('processing all pending swaps');
                $this->dispatch(new ProcessPendingSwaps());
                $this->info('done');
            } else {
                // get a single swap and process it
                $swap_id = $this->input->getArgument('swap-id');
                if (!$swap_id) {
                    $this->error("Please specify a swap id or --all");
                    return;
                }
                $swap_repository = app('Swapbot\Repositories\SwapRepository');
                $swap = $swap_repository->findByUuid($swap_id);
                if (!$swap) { $swap = $swap_repository->findByID($swap_id); }
                if (!$swap) { throw new Exception("Unable to find swap", 1); }

                $this->comment('Found swap '.$swap['uuid'].' for bot '.$swap->bot['name']);
                if ($swap->isPending()) {
                    $this->comment('Process state '.$swap->bot['state']);
                    $this->dispatch(new ProcessPendingSwap($swap));
                    $this->info('done');
                } else {
                    $this->comment('This swap was not in a pending state and won\'t be processed.  It was in state '.$swap['state']);
                }

            }

        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            throw $e;
        }
    }

}
