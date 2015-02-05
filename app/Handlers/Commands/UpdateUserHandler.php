<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Support\Facades\Log;
use Swapbot\Commands\UpdateUser;
use Swapbot\Http\Requests\User\Transformers\UserTransformer;
use Swapbot\Http\Requests\User\Validators\UpdateUserValidator;
use Swapbot\Repositories\UserRepository;

class UpdateUserHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(UpdateUserValidator $validator, UserTransformer $transformer, UserRepository $repository)
    {
        $this->validator   = $validator;
        $this->transformer = $transformer;
        $this->repository  = $repository;
    }

    /**
     * Handle the command.
     *
     * @param  UpdateUser  $command
     * @return void
     */
    public function handle(UpdateUser $command)
    {
        $user         = $command->user;
        $update_vars = $command->attributes;

        // transform
        $update_vars = $this->transformer->santizeAttributes($update_vars, $this->validator->getRules());

        // validate
        $this->validator->validate($update_vars);

        // if valid, update the user
        $user_model = $this->repository->update($user, $update_vars);

        return null;
    }

}
