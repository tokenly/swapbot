<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\BotEventCreated;
use Swapbot\Events\BotstreamEventCreated;
use Swapbot\Events\SwapEventCreated;
use Swapbot\Events\SwapstreamEventCreated;
use Swapbot\Models\BotEvent;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tokenly\LaravelEventLog\Facade\EventLog;

class TestPushBotEventCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:push-bot-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pushes a test event to the client';


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['bot-id', InputArgument::REQUIRED, 'Bot ID'],
            ['event', InputArgument::REQUIRED, 'Event JSON'],
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
            ['swap-id', 's', InputOption::VALUE_OPTIONAL, 'Swap ID'],
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

        // try a file
        $event_arg = $this->input->getArgument('event');
        if (strstr($event_arg, '{')) {
            // interpret as raw JSON
            $event = json_decode($event_arg, true);
        } else {
            // assume file
            if (file_exists($event_arg)) {
                $event = json_decode(file_get_contents($event_arg), true);
            } else {
                $this->error("File $event_arg not found");
                return;
            }
        }

        if (!$event) {
            throw new Exception("Unable to decode event", 1);
        }
        if (!isset($event['level'])) { $event['level'] = 200; }

        $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByUuid($bot_id);
        if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
        if (!$bot) {
            throw new Exception("Unable to find bot", 1);
        }

        $swap_id = $this->input->getOption('swap-id');
        $swap = null;
        if ($swap_id) {
            $swap_repository = app('Swapbot\Repositories\SwapRepository');
            $swap = $swap_repository->findByUuid($swap_id);
            if (!$swap) { $swap = $swap_repository->findByID($swap_id); }
            if (!$swap) { throw new Exception("Unable to find swap", 1); }

            // swapstream event
            $this->info("Sending Swapstreamevent for swap {$swap['uuid']} in bot ".$bot['name']." ({$bot['uuid']})");
            $event['swapUuid'] = $swap['uuid'];
            Event::fire(new SwapEventCreated($swap, $bot, $event));
    } else {
            // botstream event
            $this->info("Sending Botstreamevent for bot ".$bot['name']." ({$bot['uuid']})");
            Event::fire(new BotstreamEventCreated($bot, $event));
            // Event::fire(new BotEventCreated($bot, $event));
        }


        $this->info("done");
    }

}
