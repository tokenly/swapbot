<?php

namespace Swapbot\Console\Commands\Bot;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\DeleteBot;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tokenly\LaravelEventLog\Facade\EventLog;

class DeleteBotCommand extends Command {

    use ConfirmableTrait;
    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently Deletes a Swapbot';


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
            ['dry-run' , 'd',  InputOption::VALUE_NONE, 'Dry Run'],
            ['force',    null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $bot_id = $this->input->getArgument('bot-id');
        $is_dry_run = !!$this->input->getOption('dry-run');

        // confirm
        if (!$is_dry_run) {
            // require confirmation
            if (!$this->confirmToProceed()) { return; }
        }

        if ($is_dry_run) { $this->comment("[Dry Run]"); }

        // load the bot
        $bot_repository = app('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByUuid($bot_id);
        if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
        if (!$bot) {
            $this->error("Unable to find bot with id $bot_id");
            return;
        }

        try {
            if ($is_dry_run) {
                $this->comment("[Dry Run] Would delete bot {$bot['name']} ({$bot['uuid']})");
            } else {
                $this->comment("Deleting bot {$bot['name']} ({$bot['uuid']})");
                $this->dispatch(new DeleteBot($bot));
            }
        } catch (Exception $e) {
            // log any failure
            EventLog::logError('botdelete.failed', $e);
            $this->error("Failed: ".$e->getMessage());
        }

        $this->info("done");
    }

}
