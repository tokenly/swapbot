<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Events\BotUpdated;
use Swapbot\Swap\Logger\BotEventLogger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class IndexBotsCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:index-bots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Indexes bots for search.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {
            $bot_id = $this->input->getArgument('bot-id');

            $bot_repository = app('Swapbot\Repositories\BotRepository');

            if ($bot_id) {
                $bot = $bot_repository->findByUuid($bot_id);
                if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
                if (!$bot) { throw new Exception("Unable to find bot", 1); }
                $bots = [$bot];
            } else {
                $bots = $bot_repository->findAll();
            }

            $handler = app('Swapbot\Handlers\Events\BotIndexHandler');
            foreach($bots as $bot) {
                $this->comment('Indexing bot '.$bot['name']);;
                
                $event = new BotUpdated($bot);
                $handler->botUpdatedOrCreated($event);
            }

        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            throw $e;
        }

        $this->info('done');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['bot-id', InputArgument::OPTIONAL, 'Bot ID'],
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
        ];
    }

}
