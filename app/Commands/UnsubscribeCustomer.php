<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;
use Swapbot\Models\Customer;

class UnsubscribeCustomer extends Command {

    var $customer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

}
