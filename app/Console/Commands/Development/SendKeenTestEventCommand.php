<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Events\BotUpdated;
use Tokenly\LaravelKeenEvents\Facade\KeenEvents;
use Swapbot\Swap\Logger\BotEventLogger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SendKeenTestEventCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:send-keen-test-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a test event';

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
            $collection = $this->argument('collection');
            $event = json_decode($this->argument('event'), true);
            KeenEvents::send($collection, $event);

            // send a second event
            $event['value'] = rand(1, 999);
            KeenEvents::send($collection, $event);

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
        $number = rand(1, 999);
        return [
            ['collection', InputArgument::OPTIONAL, 'Collection Name', 'testCollection'],
            ['event', InputArgument::OPTIONAL, 'Event JSON', '{"foo": "bar", "value": '.$number.'}'],
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
