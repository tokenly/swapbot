<?php

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery as m;
use Swapbot\Commands\SendEmail;
use Swapbot\Commands\UnsubscribeCustomer;
use Swapbot\Repositories\CustomerRepository;
use \PHPUnit_Framework_Assert as PHPUnit;

class CustomerSubscriptionTest extends TestCase {

    protected $use_database = true;

    use DispatchesCommands;

    public function testCustomerToken()
    {
        $customer = app('CustomerHelper')->newSampleCustomer();
        PHPUnit::assertEquals(24, strlen($customer['unsubscribe_token']), "Unexpected unsubscribe_token length of ".strlen($customer['unsubscribe_token']));
    }

    public function testUsubscribeAction()
    {
        $customer = app('CustomerHelper')->newSampleCustomer();

        // unsubscribe
        $this->dispatch(new UnsubscribeCustomer($customer));

        // reload the customer
        //   and verify that they are unsubscribed
        $customer = app('Swapbot\Repositories\CustomerRepository')->findById($customer['id']);
        PHPUnit::assertEquals(false, $customer->isActive());
    }


    public function testUsubscribeController()
    {
        $customer = app('CustomerHelper')->newSampleCustomer();

        // call the customer id and token route
        $response_content = $this->sendRequest("GET", "/public/unsubscribe/{$customer['uuid']}/{$customer['unsubscribe_token']}");        
        PHPUnit::assertContains("successfully unsubscribed", $response_content);

        // reload the customer
        //   and verify that they are unsubscribed
        $customer = app('Swapbot\Repositories\CustomerRepository')->findById($customer['id']);
        PHPUnit::assertEquals(false, $customer->isActive());
    }


    public function testUsubscribeControllerError()
    {
        $customer = app('CustomerHelper')->newSampleCustomer();

        // call the customer id and token route
        $response_content = $this->sendRequest("GET", "/public/unsubscribe/baduuid/{$customer['unsubscribe_token']}");
        PHPUnit::assertContains("email subscription was not found", $response_content);

        // call the customer id and token route
        $response_content = $this->sendRequest("GET", "/public/unsubscribe/{$customer['uuid']}/badtoken");        
        PHPUnit::assertContains("email subscription token was not found", $response_content);
    }

    protected function sendRequest($method, $uri, $parameters=[], $cookies=[], $files=[], $server=[], $content=null) {
        $request = Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(200, $response->getStatusCode());
        return $response->getContent();
    }



    ////////////////////////////////////////////////////////////////////////


}
