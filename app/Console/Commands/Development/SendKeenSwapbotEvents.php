<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Events\BotEventCreated;
use Swapbot\Events\BotUpdated;
use Swapbot\Events\SwapEventCreated;
use Swapbot\Swap\Logger\BotEventLogger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tokenly\LaravelKeenEvents\Facade\KeenEvents;

class SendKeenSwapbotEvents extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:send-keen-swapbot-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends historical events to keen';

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
            $handler = app('Swapbot\Handlers\Events\KeenEventsHandler');
            $bot_event_repository = app('Swapbot\Repositories\BotEventRepository');

            // do every bot event
            foreach ($bot_event_repository->findAll() as $bot_event_model) {
                $bot = $bot_event_model->bot;
                $swap = $bot_event_model->swap;
                $serialized_bot_event_model = $bot_event_model->serializeForAPI();

                // fire a bot event
                $event = new BotEventCreated($bot, $serialized_bot_event_model);
                $handler->botEventCreated($event);

                // also fire a swap event if this is a swap event
                if ($swap) {
                    $event = new SwapEventCreated($swap, $bot, $serialized_bot_event_model);
                    $handler->swapEventCreated($event);
                }
            }

        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            throw $e;
        }

        $this->info('done');
    }

}
