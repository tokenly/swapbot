<?php

namespace Swapbot\Handlers\Commands;

use Swapbot\Commands\CreateCustomer;
use Swapbot\Http\Requests\Customer\Transformers\CustomerTransformer;
use Swapbot\Http\Requests\Customer\Validators\CreateCustomerValidator;
use Swapbot\Repositories\CustomerRepository;

class CreateCustomerHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(CreateCustomerValidator $validator, CustomerTransformer $transformer, CustomerRepository $repository)
    {
        $this->validator   = $validator;
        $this->transformer = $transformer;
        $this->repository  = $repository;
    }


    /**
     * Handle the command.
     *
     * @param  CreateCustomer  $command
     * @return void
     */
    public function handle(CreateCustomer $command)
    {
        $create_vars = $command->attributes;

        // transform
        $create_vars = $this->transformer->santizeAttributes($create_vars, $this->validator->getRules());

        // validate
        $this->validator->validate($create_vars);

        // if valid, create the customer
        $customer_model = $this->repository->create($create_vars);

        return null;
    }

}
