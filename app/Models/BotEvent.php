<?php

namespace Swapbot\Models;

use Swapbot\Models\Base\APIModel;
use Swapbot\Models\Traits\CreatedAtDateOnly;

class BotEvent extends APIModel {

    use CreatedAtDateOnly;

    const LEVEL_DEBUG     = 100;
    const LEVEL_INFO      = 200;
    const LEVEL_NOTICE    = 250;
    const LEVEL_WARNING   = 300;
    const LEVEL_ERROR     = 400;
    const LEVEL_CRITICAL  = 500;
    const LEVEL_ALERT     = 550;
    const LEVEL_EMERGENCY = 600;

    protected $api_attributes = ['id', 'level', 'event', 'created_at', 'serial', ];

    public function setEventAttribute($event) { $this->attributes['event'] = json_encode($event); }
    public function getEventAttribute() { return json_decode($this->attributes['event'], true); }

    public function setActiveAttribute($active) { $this->attributes['active'] = $active ? 1 : 0; }
    public function getActiveAttribute() { return !!$this->attributes['active']; }


    // public function setLevelAttribute($level) { $this->attributes['level'] = $level ? 1 : 0; }
    public function getLevelAttribute() { return intval($this->attributes['level']); }





}
