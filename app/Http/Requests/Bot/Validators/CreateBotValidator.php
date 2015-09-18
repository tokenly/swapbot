<?php

namespace Swapbot\Http\Requests\Bot\Validators;

use Swapbot\Billing\PaymentPlans;
use Swapbot\Http\Requests\Bot\Validators\BotValidator;

class CreateBotValidator extends BotValidator {

    protected $rules = [
        'uuid'                        => '',
        'name'                        => 'required',
        'url_slug'                    => 'required|min:8|max:80',
        'description'                 => 'required',
        'user_id'                     => 'numeric',
        'return_fee'                  => 'required|numeric|min:0.00001|max:0.001',
        'payment_plan'                => 'required',
        'confirmations_required'      => 'required|integer|min:2|max:6',
        'background_image_id'         => '',
        'logo_image_id'               => '',
        'background_overlay_settings' => '',
    ];


    protected function initValidatorRules() {
        $payment_plans = app('Swapbot\Billing\PaymentPlans');
        $ids = $payment_plans->allPaymentPlanNames();
        $this->rules['payment_plan'] .= '|in:'.implode(',', $ids);
    }

}

