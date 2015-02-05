<?php namespace Swapbot\Handlers\Commands;

use Swapbot\Commands\DeleteUser;
use Swapbot\Repositories\UserRepository;

class DeleteUserHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository  = $repository;
    }

    /**
     * Handle the command.
     *
     * @param  DeleteUser  $command
     * @return void
     */
    public function handle(DeleteUser $command)
    {
        $user = $command->user;

        // delete the user
        $this->repository->delete($user);

        return null;
    }

}
