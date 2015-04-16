<?php

namespace Swapbot\Http\Requests\Bot\Validators;

use Swapbot\Billing\PaymentPlans;
use Swapbot\Http\Requests\Bot\Validators\BotValidator;

class CreateBotValidator extends BotValidator {

    protected $rules = [
        'uuid'                   => '',
        'name'                   => 'required',
        'description'            => 'required',
        'user_id'                => 'numeric',
        'return_fee'             => 'required|numeric|min:0.00001|max:0.001',
        'payment_plan'           => 'required',
        'confirmations_required' => 'required|integer|min:2|max:6',
    ];


    protected function initValidatorRules() {
        $payment_plans = app('Swapbot\Billing\PaymentPlans');
        $ids = array_keys($payment_plans->allPaymentPlans());
        $this->rules['payment_plan'] .= '|in:'.implode(',', $ids);
    }

}
