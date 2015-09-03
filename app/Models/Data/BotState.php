<?php

namespace Swapbot\Models\Data;

use Metabor\Statemachine\State;

class BotState extends State {

    const BRAND_NEW     = 'brandnew';
    const LOW_FUEL      = 'lowfuel';
    const ACTIVE        = 'active';
    const INACTIVE      = 'inactive'; // manually deactivated

    const PAYING        = 'paying'; // temporary - will go back to active or fail to unpaid
    const UNPAID        = 'unpaid';

    const SHUTTING_DOWN = 'shuttingDown';
    const SHUTDOWN      = 'shutdown';

    public function isActive() {
        switch ($this->getName()) {
            case self::ACTIVE:
            case self::SHUTTING_DOWN:
                return true;
        }

        return false;
    }

}
