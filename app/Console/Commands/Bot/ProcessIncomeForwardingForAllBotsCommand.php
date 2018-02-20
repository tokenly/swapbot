<?php

namespace Swapbot\Console\Commands\Bot;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\ProcessIncomeForwardingForAllBots;
use Symfony\Component\Console\Input\InputOption;

class ProcessIncomeForwardingForAllBotsCommand extends Command
{

    use ConfirmableTrait;
    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:process-income-forwarding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes income forwarding for all bots';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {

        return [
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
            ['bot-uuid', 'b', InputOption::VALUE_OPTIONAL, 'Limit processing only to this Bot'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        // require confirmation
        if (!$this->confirmToProceed()) {return;}

        $limit_to_bot_id = null;

        $bot_uuid = $this->input->getOption('bot-uuid');
        if ($bot_uuid) {
            $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');
            $bot = $bot_repository->findByUuid($bot_uuid);
            if ($bot) {
                $limit_to_bot_id = $bot['id'];
                $this->comment('limiting to bot ' . $bot['name']);
            }
        }

        try {
            $this->dispatch(new ProcessIncomeForwardingForAllBots($_override_delay = true, $limit_to_bot_id));

        } catch (Exception $e) {
            $this->error($e->getMessage());
            return;
        }
        $this->info("done");
    }

}
