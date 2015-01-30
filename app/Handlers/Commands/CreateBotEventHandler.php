<?php namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Swapbot\Commands\CreateBotEvent;
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
        $create_vars = [
            'bot_id' => $command->bot['id'],
            'level'  => $command->level,
            'event'  => $command->event,
        ];

        // create the bot event
        $bot_event_model = $this->repository->create($create_vars);

        return null;
    }

}
