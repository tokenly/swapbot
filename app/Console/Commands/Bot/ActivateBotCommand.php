<?php

namespace Swapbot\Console\Commands\Bot;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Swapbot\Commands\ActivateBot;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ActivateBotCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:activate-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activates a bot';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('bot-id', InputArgument::REQUIRED, 'Bot ID')
            ->setHelp(<<<EOF
Activates a bot.  Creates any missing attributes from XChain.
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
        $bot = $bot_repository->findByID($bot_id);
        if (!$bot) {
            // try uuid
            $bot = $bot_repository->findByUuid($bot_id);
        }
        if (!$bot) {
            throw new Exception("Unable to find bot", 1);
        }

        $this->info("Updating balances for bot ".$bot['name']." ({$bot['uuid']})");

        try {
            $this->dispatch(new ActivateBot($bot));
        } catch (Exception $e) {
            // log any failure
            EventLog::logError('activate.failed', $e);
            $this->error("Failed: ".$e->getMessage());
        }

        $this->info("done");
    }

}
