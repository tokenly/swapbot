<?php

use Mockery as m;

class TokenpassHelper  {

    function __construct() {
        $this->called_api_methods = new TokenpassCalledAPIMethods();
    }


    public function mockTokenpassAPI() {

        $mock_tokenpass_api = m::mock('Tokenly\TokenpassClient\TokenpassAPI', function ($mock_builder) {
            $functions_to_mock = [
                'registerProvisionalSource'       => null,
                'getProvisionalSourceProofSuffix' => '1myaddress_1000deadbeef00011000deadbeef0001',
            ];

            foreach($functions_to_mock as $method_name => $method_return_value) {
                $mock_builder->shouldReceive($method_name)->andReturnUsing(function() use ($method_name, $method_return_value) {
                    $this->called_api_methods->recordCall($method_name, func_get_args());

                    return $method_return_value;
                });
            }
        })->makePartial();

        // overwrite Laravel binding
        app()->bind('Tokenly\TokenpassClient\TokenpassAPI', function($app) use ($mock_tokenpass_api) {
            return $mock_tokenpass_api;
        });

        return $this->called_api_methods;
    }


}
