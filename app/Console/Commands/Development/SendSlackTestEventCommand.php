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

class SendSlackTestEventCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:send-slack-test-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a test event to slack';

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
            $title = $this->argument('title');
            $text = $this->argument('text');
            KeenEvents::sendSlackEvent($title, $text);
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
            ['title', InputArgument::OPTIONAL, 'Title', 'Test Swapbot Event'],
            ['text', InputArgument::OPTIONAL, 'Text', 'This is the text'],
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
