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

class ChangeBotStateCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:change-bot-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually changes a bot state';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('bot-id', InputArgument::REQUIRED, 'Bot ID')
            ->addArgument('event', InputArgument::REQUIRED, 'State Change Event')
            ->setHelp(<<<EOF
Changes a bot's state by triggering a state change event (monthlyFeePaid, botFueled, fuelExhausted, paymentExhausted, paid, startShutdown, completeShutdown).
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
        $event = $this->input->getArgument('event');

        $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByUuid($bot_id);
        if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
        if (!$bot) {
            throw new Exception("Unable to find bot", 1);
        }

        $this->info("tiggering state change event: $event");
        $bot->stateMachine()->triggerEvent($event);

        $this->info("done");
    }

}
