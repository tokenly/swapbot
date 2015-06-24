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
            ['id', 'i', InputOption::VALUE_OPTIONAL, 'Filter by ID'],
            ['full', 'f', InputOption::VALUE_NONE, 'Show Full Bot Details'],
        ];
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

        $show_full = !!$this->input->getOption('full');

        foreach($bots as $bot) {
            if ($show_full) {
                $output = json_encode($bot, 192);
            } else {
                $fields = ['id', 'uuid', 'name', 'username', 'address', 'state'];
                $short_bot = [];
                foreach($fields as $field) {
                    $short_bot[$field] = $bot[$field];
                }
                $output = json_encode($short_bot, 192);
            }
            $this->line($output);
        }
    }

}
