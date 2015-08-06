<?php

namespace Swapbot\Models;

use Illuminate\Database\Eloquent\Model;
use Swapbot\Models\Traits\CreatedAtDateOnly;

class Block extends Model {

    use CreatedAtDateOnly;

    protected static $unguarded = true;


}
