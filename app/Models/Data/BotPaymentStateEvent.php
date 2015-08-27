<?php

namespace Swapbot\Models\Data;


class BotPaymentStateEvent {

    const ENTERED_OK       = 'enteredOK';

    const ENTERED_NOTICE   = 'enteredNotice';
    const ENTERED_SOON     = 'enteredSoon';
    const ENTERED_URGENT   = 'enteredUrgent';

    const ENTERED_PAST_DUE = 'enteredPastDue';

}
