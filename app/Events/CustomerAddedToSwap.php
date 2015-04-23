<?php

namespace Swapbot\Events;

use Swapbot\Events\Event;
use Swapbot\Models\Customer;
use Swapbot\Models\Swap;

class CustomerAddedToSwap extends Event {

    var $customer;
    var $swap;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, Swap $swap)
    {
        $this->customer = $customer;
        $this->swap     = $swap;
    }

}
