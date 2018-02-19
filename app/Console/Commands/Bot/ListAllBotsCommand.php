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
            ['user', 'u', InputOption::VALUE_NONE, 'Include User Information'],
            ['active', 'a', InputOption::VALUE_NONE, 'Active Only'],
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
        $active_only = !!$this->input->getOption('active');
        $with_user = !!$this->input->getOption('user');

        foreach($bots as $bot) {
            if ($active_only) {
                if (!$bot->isActive() OR $bot->isShuttingDown()) {
                    continue;
                }
            }
            if ($show_full) {
                $output_data = json_decode(json_encode($bot, 192), true);
            } else {
                $fields = ['id', 'uuid', 'name', 'username', 'address', 'state'];
                $short_bot = [];
                foreach($fields as $field) {
                    $short_bot[$field] = $bot[$field];
                }
                $output_data = json_decode(json_encode($short_bot, 192), true);
            }

            $output_data['publicUrl'] = $bot->getPublicBotURL();

            if ($with_user) {
                $user = $bot->user;
                $output_data['user'] = [
                    'name'  => $user['name'],
                    'email' => $user['email'],
                ];
            }

            $output = json_encode($output_data, 192);
            $this->line($output);
        }
    }

}
