<?php

namespace Swapbot\Models\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Tokenly\LaravelApiProvider\Model\SerializesForAPI;
use \Exception;

/*
* APIModel
*/
class APIModel extends Model
{

    use SerializesForAPI;

    protected static $unguarded = true;

    protected $api_attributes = ['id'];


}
