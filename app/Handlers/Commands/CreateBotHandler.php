<?php namespace Swapbot\Handlers\Commands;

use Swapbot\Commands\CreateBot;
use Swapbot\Http\Requests\Bot\Transformers\BotTransformer;
use Swapbot\Http\Requests\Bot\Validators\CreateBotValidator;
use Swapbot\Repositories\BotRepository;

class CreateBotHandler {

	/**
	 * Create the command handler.
	 *
	 * @return void
	 */
	public function __construct(CreateBotValidator $validator, BotTransformer $transformer, BotRepository $repository)
	{
		$this->validator = $validator;
		$this->transformer = $transformer;
		$this->repository = $repository;
	}

	/**
	 * Handle the command.
	 *
	 * @param  CreateBot  $command
	 * @return void
	 */
	public function handle(CreateBot $command)
	{
		$create_vars = $command->attributes;

		// transform
		$create_vars = $this->transformer->santizeAttributes($create_vars, $this->validator->getRules());

		// validate
		$this->validator->validate($create_vars);

		// if valid, create the bot
		$bot_model = $this->repository->create($create_vars);

		return null;
	}

}
