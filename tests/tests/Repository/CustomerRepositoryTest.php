<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class CustomerRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testCustomerRepository()
    {
        $create_model_fn = function() {
            return $this->app->make('CustomerHelper')->newSampleCustomer();
        };
        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('Swapbot\Repositories\CustomerRepository'));
        // $helper->use_uuid = false;

        $helper->testLoad();
        $helper->cleanup()->testUpdate(['email' => 'foo@tokenly.co']);
        $helper->cleanup()->testDelete();
    }

    public function testFindCustomersBySwap()
    {
        $swap1 = app('SwapHelper')->newSampleSwap();
        $swap2 = app('SwapHelper')->newSampleSwap();
        $customer_1 = app()->make('CustomerHelper')->newSampleCustomer($swap1);
        $customer_2 = app()->make('CustomerHelper')->newSampleCustomer($swap1, ['email' => 'dude2@tokenly.co',]);
        $customer_3 = app()->make('CustomerHelper')->newSampleCustomer($swap2);

        $customer_repository = app('Swapbot\Repositories\CustomerRepository');
        

        $loaded_customers = $customer_repository->findBySwap($swap1);
        PHPUnit::assertCount(2, $loaded_customers);
        PHPUnit::assertEquals($customer_1['id'], $loaded_customers[0]['id']);
        PHPUnit::assertEquals($customer_2['id'], $loaded_customers[1]['id']);

        $loaded_customers = $customer_repository->findBySwap($swap2);
        PHPUnit::assertCount(1, $loaded_customers);
        PHPUnit::assertEquals($customer_3['id'], $loaded_customers[0]['id']);
    }



}
