<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\CreateBotEvent;
use Swapbot\Models\BotEvent;
use Swapbot\Providers\EventLog\Facade\EventLog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TestCreateBotEventCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:send-bot-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a test event';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('bot-id', InputArgument::REQUIRED, 'Bot ID')
            ->addArgument('event', InputArgument::REQUIRED, 'Event JSON')
            ->addOption('level', 'l', InputOption::VALUE_OPTIONAL, 'Event level', BotEvent::LEVEL_INFO)
            ->setHelp(<<<EOF
Sends a test event
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
        $event = json_decode($this->input->getArgument('event'));
        if (!$event) {
            throw new Exception("Unable to decode event", 1);
        }

        $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByID($bot_id);
        if (!$bot) {
            // try uuid
            $bot = $bot_repository->findByUuid($bot_id);
        }
        if (!$bot) {
            throw new Exception("Unable to find bot", 1);
        }

        $this->info("Creating event for bot ".$bot['name']." ({$bot['uuid']})");
        $level = $this->input->getOption('level');
        $this->dispatch(new CreateBotEvent($bot, $level, $event));
        $this->info("done");
    }

}
