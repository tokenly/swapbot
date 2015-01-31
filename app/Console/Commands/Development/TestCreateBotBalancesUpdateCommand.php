<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotBalancesUpdated;
use Swapbot\Providers\EventLog\Facade\EventLog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TestCreateBotBalancesUpdateCommand extends Command {


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:send-bot-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a test balances update event';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('bot-id', InputArgument::REQUIRED, 'Bot ID')
            ->addArgument('balances', InputArgument::REQUIRED, 'Event JSON')
            ->setHelp(<<<EOF
Sends a test balances event
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
        $balances = json_decode($this->input->getArgument('balances'));
        if (!$balances) {
            throw new Exception("Unable to decode balances", 1);
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

        $this->info("Creating balances for bot ".$bot['name']." ({$bot['uuid']})");
        Event::fire(new BotBalancesUpdated($bot, $balances));
        $this->info("done");
    }

}
