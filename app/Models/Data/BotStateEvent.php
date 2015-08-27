<?php

namespace Swapbot\Models\Data;


class BotStateEvent {

    const FIRST_MONTHLY_FEE_PAID = 'firstMonthlyFeePaid';
    const MONTHLY_FEE_PAID       = 'monthlyFeePaid';
    const FUELED                 = 'botFueled';
    const FUEL_EXHAUSTED         = 'fuelExhausted';
    const LEASE_EXPIRED          = 'leaseExpired';
    const PAYMENT_EXHAUSTED      = 'paymentExhausted';

}
