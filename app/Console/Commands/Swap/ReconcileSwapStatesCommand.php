<?php

namespace Swapbot\Console\Commands\Swap;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\ReconcileBotSwapStates;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ReconcileSwapStatesCommand extends Command {

    use DispatchesCommands;
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:reconcile-swaps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconciles all swaps states for a bot.';

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
            ['bot-id', InputArgument::REQUIRED, 'Bot ID'],
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
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
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
            $bot_id = $this->input->getArgument('bot-id');
            $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');
            $bot = $bot_repository->findByUuid($bot_id);
            if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
            if (!$bot) { throw new Exception("Unable to find bot", 1); }


            $confirmed = $this->confirmToProceed();
            if (!$confirmed) {
                $this->error("Aborting");
                return;
            }

            $block_height = app('Swapbot\Repositories\BlockRepository')->findBestBlockHeight();
            $this->comment("Using block height $block_height");

            $this->dispatch(new ReconcileBotSwapStates($bot, $block_height));
            $this->info('done');


        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            throw $e;
        }
    }

}
