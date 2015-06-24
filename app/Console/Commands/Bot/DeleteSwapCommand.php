<?php

namespace Swapbot\Console\Commands\Bot;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\DeleteSwap;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tokenly\LaravelEventLog\Facade\EventLog;

class DeleteSwapCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:delete-swap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all swaps for a bot';


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {

        return [
            ['swap-id', InputArgument::REQUIRED, 'Swap ID'],
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
            ['dry-run' , 'd',  InputOption::VALUE_NONE, 'Dry Run'],
        ];
    }



    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $swap_id = $this->input->getArgument('swap-id');
        $is_dry_run = !!$this->input->getOption('dry-run');

        $swap_repository = $this->laravel->make('Swapbot\Repositories\SwapRepository');
        $swap = $swap_repository->findByUuid($swap_id);
        if (!$swap) { throw new Exception("Unable to find swap", 1); }

        // delete the swap
        $this->comment("Deleting swap {$swap['uuid']}");

        try {
            if ($is_dry_run) {
                $this->info("[Dry Run] Would delete swap {$swap['id']} ({$swap['uuid']})");
            } else {
                $confirmed = $this->confirm("Are you sure you want to delete swap {$swap['id']} ({$swap['uuid']})?", false);
                if (!$confirmed) {
                    $this->error("Aborting");
                    return;
                }

                $this->comment("Deleting swap {$swap['name']} ({$swap['uuid']})");
                $this->dispatch(new DeleteSwap($swap));
            }
        } catch (Exception $e) {
            // log any failure
            EventLog::logError('swapdelete.failed', $e);
            $this->error("Failed: ".$e->getMessage());
        }

        $this->comment("done");
    }

}
