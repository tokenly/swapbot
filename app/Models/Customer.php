<?php

namespace Swapbot\Models;

use Swapbot\Models\Base\APIModel;

class Customer extends APIModel {

    protected $api_attributes = ['id', 'email', 'unsubscribe_token', 'active',];

    protected static $unguarded = true;

    protected $casts = [
        'active' => 'boolean',
    ];



    public function isActive() {
        return !!$this['active'];
    }

    public function swap() {
        return $this->belongsTo('Swapbot\Models\Swap');
    }

}
