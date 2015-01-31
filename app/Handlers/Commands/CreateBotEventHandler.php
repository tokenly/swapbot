<?php namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;
use Swapbot\Commands\CreateBotEvent;
use Swapbot\Events\BotEventCreated;
use Swapbot\Repositories\BotEventRepository;

class CreateBotEventHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotEventRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Handle the command.
     *
     * @param  CreateBotEvent  $command
     * @return void
     */
    public function handle(CreateBotEvent $command)
    {
        $bot = $command->bot;

        $create_vars = [
            'bot_id' => $bot['id'],
            'level'  => $command->level,
            'event'  => $command->event,
        ];

        // create the bot event
        $bot_event_model = $this->repository->create($create_vars);

        // fire an event
        Event::fire(new BotEventCreated($bot, $bot_event_model->serializeForAPI()));

        return null;
    }

}
