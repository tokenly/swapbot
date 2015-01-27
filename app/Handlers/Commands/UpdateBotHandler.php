<?php namespace Swapbot\Handlers\Commands;

use Swapbot\Commands\UpdateBot;
use Swapbot\Http\Requests\Bot\Transformers\BotTransformer;
use Swapbot\Http\Requests\Bot\Validators\UpdateBotValidator;
use Swapbot\Repositories\BotRepository;

class UpdateBotHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(UpdateBotValidator $validator, BotTransformer $transformer, BotRepository $repository)
    {
        $this->validator   = $validator;
        $this->transformer = $transformer;
        $this->repository  = $repository;
    }

    /**
     * Handle the command.
     *
     * @param  UpdateBot  $command
     * @return void
     */
    public function handle(UpdateBot $command)
    {
        $bot         = $command->bot;
        $update_vars = $command->attributes;

        // transform
        $update_vars = $this->transformer->santizeAttributes($update_vars, $this->validator->getRules());

        // validate
        $this->validator->validate($update_vars);

        // if valid, update the bot
        $bot_model = $this->repository->update($bot, $update_vars);

        return null;
    }

}
