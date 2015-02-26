<?php

namespace Swapbot\Models;

use Illuminate\Database\Eloquent\Model;
use Swapbot\Models\Data\SwapConfig;

class Swap extends Model {

    protected $casts = [
        'processed'  => 'boolean',
        'definition' => 'json',
        'receipt'    => 'json',
    ];

    protected static $unguarded = true;

    public function getSwapConfig() {
        return SwapConfig::createFromSerialized($this['definition']);
    }

    public function wasSent() {
        return ($this['state'] == 'sent');
    }
}
