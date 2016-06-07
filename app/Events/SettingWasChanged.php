<?php

namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Setting;

class SettingWasChanged extends Event
{

    var $setting;
    var $event_type;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Setting $setting, $event_type)
    {
        $this->setting    = $setting;
        $this->event_type = $event_type;
    }

}
