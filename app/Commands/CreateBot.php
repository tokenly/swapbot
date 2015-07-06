<?php namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\User;

class CreateBot extends Command {

    var $attributes;
    var $user;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($attributes, User $user)
    {
        $this->attributes = $attributes;
        $this->user       = $user;
    }

}
