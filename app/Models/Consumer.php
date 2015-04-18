<?php

namespace Swapbot\Models;

use Illuminate\Database\Eloquent\Model;

class Consumer extends Model {

    protected static $unguarded = true;

    public function swap() {
        return $this->belongsTo('Swapbot\Models\Swap');
    }

}
