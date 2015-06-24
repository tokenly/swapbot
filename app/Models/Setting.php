<?php

namespace Swapbot\Models;

use Swapbot\Models\Base\APIModel;

class Setting extends APIModel {

    protected $api_attributes = ['id', 'name', 'value', 'created_at', 'updated_at', ];

    protected $casts = [
        'value'   => 'json',
    ];


}
