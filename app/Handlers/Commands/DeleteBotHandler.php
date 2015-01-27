<?php namespace Swapbot\Handlers\Commands;

use Swapbot\Commands\DeleteBot;
use Swapbot\Repositories\BotRepository;

class DeleteBotHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $repository)
    {
        $this->repository  = $repository;
    }

    /**
     * Handle the command.
     *
     * @param  DeleteBot  $command
     * @return void
     */
    public function handle(DeleteBot $command)
    {
        $bot = $command->bot;

        // delete the bot
        $this->repository->delete($bot);

        return null;
    }

}
