<?php

namespace Swapbot\Providers\Billing;

use Exception;
use Illuminate\Support\ServiceProvider;

class PaymentPlansServiceProvider extends ServiceProvider {

    public function register() {
        
    }

    public function boot()
    {
        $this->app->make('events')->subscribe('Swapbot\Billing\PaymentPlans');
    }



    

}
