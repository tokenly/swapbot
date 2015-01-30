<?php

namespace Swapbot\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {

    protected static $unguarded = true;

    public function setSwapReceiptsAttribute($swap_receipts) { $this->attributes['swap_receipts'] = json_encode($swap_receipts); }
    public function getSwapReceiptsAttribute() { return isset($this->attributes['swap_receipts']) ? json_decode($this->attributes['swap_receipts'], true) : []; }


}
