<?php

namespace Swapbot\Console\Commands\Bot;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ListAllBotsCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:list-bots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List All Bots';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'Filter by ID')
            ->setHelp(<<<EOF
Show all bots
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

        $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');
        $id = $this->input->getOption('id');
        if ($id) {
            $bot = $bot_repository->findByID($id);
            if ($bot) {
                $bots[] = $bot;
            } else {
                $bots = [];
            }
        } else {
            $bots = $bot_repository->findAll();
        }

        foreach($bots as $bot) {
            $this->line(json_encode($bot, 192));
        }
    }

}
