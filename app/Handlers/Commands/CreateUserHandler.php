<?php namespace Swapbot\Handlers\Commands;

use Swapbot\Commands\CreateUser;
use Swapbot\Http\Requests\User\Transformers\UserTransformer;
use Swapbot\Http\Requests\User\Validators\CreateUserValidator;
use Swapbot\Repositories\UserRepository;

class CreateUserHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(CreateUserValidator $validator, UserTransformer $transformer, UserRepository $repository)
    {
        $this->validator = $validator;
        $this->transformer = $transformer;
        $this->repository = $repository;
    }

    /**
     * Handle the command.
     *
     * @param  CreateUser  $command
     * @return void
     */
    public function handle(CreateUser $command)
    {
        $create_vars = $command->attributes;

        // transform
        $create_vars = $this->transformer->santizeAttributes($create_vars, $this->validator->getRules());

        // validate
        $this->validator->validate($create_vars);

        // if valid, create the user
        $user_model = $this->repository->create($create_vars);

        return null;
    }

}
