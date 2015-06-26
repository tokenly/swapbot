<?php

namespace Swapbot\Http\Controllers\API\Bot;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Swapbot\Billing\PaymentPlans;
use Swapbot\Http\Controllers\API\Base\APIController;

class PlansController extends APIController {

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getPaymentPlans(PaymentPlans $payment_plans)
    {
        return $payment_plans->allPaymentPlans();
    }


}
