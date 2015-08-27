<?php

namespace Swapbot\Models\Data;

use Metabor\Statemachine\State;

class BotPaymentState extends State {

    const NONE     = 'none';
    const PAST_DUE = 'pastdue';
    const URGENT   = 'urgent'; // 1  day
    const SOON     = 'soon';   // 7  days
    const NOTICE   = 'notice'; // 14 days
    const OK       = 'ok';

    public function isPaid() {
        switch ($this->getName()) {
            case self::PAST_DUE:
                return false;
        }

        return true;
    }

}
