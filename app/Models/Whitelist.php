<?php

namespace Swapbot\Models;

use Tokenly\LaravelApiProvider\Model\APIModel;
use Exception;

class Whitelist extends APIModel {

    protected $api_attributes = ['id', 'name', 'data',];

    protected $casts = [
        'data' => 'json',
    ];

}
