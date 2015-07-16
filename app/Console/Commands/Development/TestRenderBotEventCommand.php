<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Swapbot\Models\BotEvent;
use Swapbot\Swap\Logger\OutputTransformer\Facade\BotEventOutputTransformer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tokenly\LaravelEventLog\Facade\EventLog;

class TestRenderBotEventCommand extends Command {


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:render-bot-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renders an event';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('event-id', InputArgument::REQUIRED, 'Event ID')
            ->setHelp(<<<EOF
Renders an  event
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
        $event_id = $this->input->getArgument('event-id');


        $event_repository = $this->laravel->make('Swapbot\Repositories\BotEventRepository');
        $event = $event_repository->findByUuid($event_id);
        if (!$event) { $event = $event_repository->findByID($event_id); }
        if (!$event) {
            throw new Exception("Unable to find event", 1);
        }

        $this->info(BotEventOutputTransformer::buildMessage($event));

        $this->comment("done");
    }

}
