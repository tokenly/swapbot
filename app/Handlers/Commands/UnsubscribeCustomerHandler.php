<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Swapbot\Commands\UnsubscribeCustomer;
use Swapbot\Repositories\CustomerRepository;

class UnsubscribeCustomerHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(CustomerRepository $customer_repository)
    {
        $this->customer_repository = $customer_repository;
    }

    /**
     * Handle the command.
     *
     * @param  UnsubscribeCustomer  $command
     * @return void
     */
    public function handle(UnsubscribeCustomer $command)
    {
        $customer = $command->customer;

        $this->customer_repository->update($customer, ['active' => false]);

        return;
    }

}
