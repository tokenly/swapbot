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

class ListBotSwapsCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:list-swaps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all swaps for a bot';


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
            ['full', 'f', InputOption::VALUE_NONE, 'Show Full Swap Details'],
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
        $show_full = !!$this->input->getOption('full');

        $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByUuid($bot_id);
        if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
        if (!$bot) { throw new Exception("Unable to find bot", 1); }

        // get all swaps
        $this->comment("Listing all swaps for bot {$bot['name']}");
        $swap_repository = $this->laravel->make('Swapbot\Repositories\SwapRepository');
        foreach ($swap_repository->findByBot($bot) as $swap) {
            if ($show_full) {
                $output = json_encode($swap, 192);
            } else {
                $fields = ['id', 'uuid', 'state', 'receipt', 'created_at', 'updated_at', 'completed_at', ];
                $short_swap = [];
                foreach($fields as $field) {
                    if (substr($field, -2) == 'at') {
                        $short_swap[$field] = "".$swap[$field];
                    } else {
                        $short_swap[$field] = $swap[$field];
                    }
                }
                $output = json_encode($short_swap, 192);
            }
            $this->line($output);
        }

        $this->comment("done");
    }

}
