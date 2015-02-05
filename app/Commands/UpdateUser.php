<?php namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\User;

class UpdateUser extends Command {

    var $user;
    var $attributes;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(User $user, $attributes)
    {
        $this->user        = $user;
        $this->attributes = $attributes;
    }

}
