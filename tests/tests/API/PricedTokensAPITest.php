<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class PricedTokensAPITest extends TestCase {

    protected $use_database = true;

    public function testPricedTokensAPI()
    {
        // setup the API tester
        $tester = $this->setupAPITester();

        $tester->testURLCallRequiresUser('/api/v1/pricedtokens');

        // now call the API
        $response = $tester->callAPIAndValidateResponse('GET', '/api/v1/pricedtokens', [], 200);

        // check response
        PHPUnit::assertEquals([
            [
                "token"  => "FOOCOIN",
                "symbol" => "FOOCOIN"
            ],
            [
                "token"  => "LTBCOIN",
                "symbol" => "LTB"
            ],
            [
                "token"  => "XCP",
                "symbol" => "XCP"
            ],
        ], $response);
    }

    // ------------------------------------------------------------------------

    protected function setupAPITester() {
        $tester = app('APITestHelper');
        $tester
            ->setURLBase('/api/v1/pricedtokens')
            ->useUserHelper(app('UserHelper'));

        return $tester;
    }

}
