<?php

namespace Swapbot\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {

    protected static $unguarded = true;


    public function wasBilled() {
        return !!$this['billed_event_id'];
    }

}
