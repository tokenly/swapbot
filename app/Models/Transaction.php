<?php

namespace Swapbot\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {

    protected static $unguarded = true;

    protected $casts = [
        'xchain_notification' => 'json',
    ];


    public function bot() {
        return $this->belongsTo('Swapbot\Models\Bot');
    }


    public function wasBilled() {
        return !!$this['billed_event_id'];
    }

}
