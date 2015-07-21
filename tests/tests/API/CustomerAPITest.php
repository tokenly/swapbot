<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class CustomerAPITest extends TestCase {

    protected $use_database = true;

    public function testSuccessfulCustomerAPICall()
    {
        $mock_mailer = app('ScenarioRunner')->installMockMailer();

        $swap_helper = app('SwapHelper');
        $swap = $swap_helper->newSampleSwap();

        $tester = app('APITestHelper');

        $created_customer_response = $tester->callAPIWithoutAuthentication('POST', '/api/v1/public/customers', [
            'email'  => 'customer001@tokenly.co',
            'swapId' => $swap['uuid'],
        ]);
        PHPUnit::assertEquals(200, $created_customer_response->getStatusCode(), "Unexpected response: ".$created_customer_response->getContent());
        $created_customer = json_decode($created_customer_response->getContent(), true);
        PHPUnit::assertNotEmpty($created_customer);

        // load the customer
        $customer_repository = app('Swapbot\Repositories\CustomerRepository');
        $actual_customer = $customer_repository->findByUUID($created_customer['id']);

        PHPUnit::assertNotEmpty($actual_customer);
        PHPUnit::assertEquals($swap['id'], $actual_customer['swap_id']);
        PHPUnit::assertEquals(0, $actual_customer['level']);

        // make sure the customer was emailed
        $actual_emails = $mock_mailer->emails;
        PHPUnit::assertCount(1, $actual_emails);
        PHPUnit::assertEquals('Swap Request Received', $actual_emails[0]['subject']);
        PHPUnit::assertEquals(['customer001@tokenly.co' => null], $actual_emails[0]['to']);
        
    }

    public function testCustomerLevelAPICall()
    {
        $mock_mailer = app('ScenarioRunner')->installMockMailer();

        $swap_helper = app('SwapHelper');
        $swap = $swap_helper->newSampleSwap();

        $tester = app('APITestHelper');

        $created_customer_response = $tester->callAPIWithoutAuthentication('POST', '/api/v1/public/customers', [
            'email'  => 'customer001@tokenly.co',
            'level'  => 20,
            'swapId' => $swap['uuid'],
        ]);
        PHPUnit::assertEquals(200, $created_customer_response->getStatusCode(), "Unexpected response: ".$created_customer_response->getContent());
        $created_customer = json_decode($created_customer_response->getContent(), true);
        PHPUnit::assertNotEmpty($created_customer);

        // load the customer
        $customer_repository = app('Swapbot\Repositories\CustomerRepository');
        $actual_customer = $customer_repository->findByUUID($created_customer['id']);

        PHPUnit::assertNotEmpty($actual_customer);
        PHPUnit::assertEquals($swap['id'], $actual_customer['swap_id']);
        PHPUnit::assertEquals(20, $actual_customer['level']);
    }


    public function testDuplicateCustomerAPICall() {
        $mock_mailer = app('ScenarioRunner')->installMockMailer();

        $swap_helper = app('SwapHelper');
        $swap = $swap_helper->newSampleSwap();

        $tester = app('APITestHelper');

        $created_customer_response = $tester->callAPIWithoutAuthentication('POST', '/api/v1/public/customers', [
            'email'  => 'customer001@tokenly.co',
            'swapId' => $swap['uuid'],
        ]);
        PHPUnit::assertEquals(200, $created_customer_response->getStatusCode(), "Unexpected response: ".$created_customer_response->getContent());
        $created_customer = json_decode($created_customer_response->getContent(), true);
        PHPUnit::assertNotEmpty($created_customer);

        // try a second time
        $created_customer_response = $tester->callAPIWithoutAuthentication('POST', '/api/v1/public/customers', [
            'email'  => 'customer001@tokenly.co',
            'swapId' => $swap['uuid'],
        ]);
        PHPUnit::assertEquals(409, $created_customer_response->getStatusCode(), "Unexpected response: ".$created_customer_response->getContent());
        $created_customer = json_decode($created_customer_response->getContent(), true);
        PHPUnit::assertNotEmpty($created_customer);


    }

}
