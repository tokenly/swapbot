<?php

namespace Swapbot\Models;

use Swapbot\Models\Base\APIModel;
use Swapbot\Models\Traits\CreatedAtDateOnly;

class BotLeaseEntry extends APIModel {

    use CreatedAtDateOnly;

    protected $api_attributes = ['id', 'start_date', 'end_date', 'created_at', ];

    protected $dates = ['start_date', 'end_date',];


    public function getPaidUpThroughDate() {
        // code
    }
}
