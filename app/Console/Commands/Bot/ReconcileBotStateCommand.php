<?php

namespace Swapbot\Console\Commands\Bot;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Swapbot\Commands\ReconcileBotState;
use Swapbot\Events\BotBalancesUpdated;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tokenly\LaravelEventLog\Facade\EventLog;

class ReconcileBotStateCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:reconcile-bot-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forces an update of the bot state';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('bot-id', InputArgument::REQUIRED, 'Bot ID')
            ->setHelp(<<<EOF
Updates the bot state
EOF
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $bot_id = $this->input->getArgument('bot-id');

        $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByUuid($bot_id);
        if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
        if (!$bot) {
            throw new Exception("Unable to find bot", 1);
        }

        $this->info("Updating balances for bot ".$bot['name']." ({$bot['uuid']})");

        try {
            $this->dispatch(new ReconcileBotState($bot));
        } catch (Exception $e) {
            // log any failure
            EventLog::logError('reconcilebotstate.failed', $e);
            $this->error("Failed: ".$e->getMessage());
        }

        $this->info("done");
    }

}
