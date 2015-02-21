<?php

namespace Swapbot\Models;

use Swapbot\Models\Base\APIModel;
use Swapbot\Models\Traits\CreatedAtDateOnly;

class BotLedgerEntry extends APIModel {

    use CreatedAtDateOnly;

    protected $api_attributes = ['id', 'created_at', 'is_credit', 'amount',];



    public function setIsCreditAttribute($is_credit) { $this->attributes['is_credit'] = $is_credit ? 1 : 0; }
    public function getIsCreditAttribute() { return !!$this->attributes['is_credit']; }

}
