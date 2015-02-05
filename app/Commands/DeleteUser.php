<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\User;

class DeleteUser extends Command {

    var $user;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user        = $user;
    }

}
