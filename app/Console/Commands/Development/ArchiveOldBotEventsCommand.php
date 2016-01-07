<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Swapbot\Swap\Logger\BotEventLogger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ArchiveOldBotEventsCommand extends Command {


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:archive-old-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a Pending Swap.';

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
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['event-name', InputArgument::REQUIRED, 'An event name to archive'],
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
            // ['all', 'a', InputOption::VALUE_NONE, 'Process all pending swaps.'],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {
            // get a single swap and process it
            $event_name = $this->input->getArgument('event-name');
            $this->comment("Archiving events with name: $event_name");

            $archived_count = 0;
            DB::transaction(function() use ($event_name, &$archived_count) {
                $bot_event_repository = app('Swapbot\Repositories\BotEventRepository');
                foreach ($bot_event_repository->slowFindByEventName($event_name) as $bot_event_model) {
                    // archive the event
                    $bot_event_repository->archive($bot_event_model);
                    // echo "Archiving {$bot_event_model['id']} {$bot_event_model['event']['name']}\n";
                    ++$archived_count;
                }
            });

        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            throw $e;
        }

        $this->comment('Done. Archived '.$archived_count.' events.');
    }

}
