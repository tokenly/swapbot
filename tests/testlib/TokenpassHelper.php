<?php

use Mockery as m;

class TokenpassHelper  {

    static $INSTANCE;

    protected $called_api_methods = null;
    protected $mock_tokenpass_api = null;

    function __construct() {
        $this->called_api_methods = new TokenpassCalledAPIMethods();
    }

    // ensure that this is only done once per application lifecycle
    public function mockTokenpassAPI() {
        if (!self::$INSTANCE) {
            self::$INSTANCE = $this;
        }

        return self::$INSTANCE->setupMock();
    }

    public function getCalledAPIMethods() {
        return $this->called_api_methods;
    }
    
    public function setupMock() {
        if (is_null($this->mock_tokenpass_api)) {
            $this->mock_tokenpass_api = m::mock('Tokenly\TokenpassClient\TokenpassAPI', function ($mock_builder) {
                $functions_to_mock = [
                    'registerProvisionalSource'       => null,
                    'getProvisionalSourceProofSuffix' => '1myaddress_1000deadbeef00011000deadbeef0001',
                    'promiseTransaction'              => function () {    return ['tx' => ['promise_id' => crc32(json_encode(func_get_args())) % 1000000]]; },
                    'updatePromisedTransaction'       => function ($id) { return ['tx' => ['promise_id' => $id]]; },
                    'deletePromisedTransaction'       => ['result' => true],
                ];

                foreach($functions_to_mock as $method_name => $method_return_value) {
                    $mock_builder->shouldReceive($method_name)->andReturnUsing(function() use ($method_name, $method_return_value) {
                        $this->called_api_methods->recordCall($method_name, func_get_args());

                        if (is_callable($method_return_value)) {
                            return call_user_func_array($method_return_value, func_get_args());
                        }

                        return $method_return_value;
                    });
                }
            })->makePartial();
        } else {
            // not the first time
            //   reset the called api methods
            $this->called_api_methods->reset();
        }

        // overwrite Laravel binding
        app()->bind('Tokenly\TokenpassClient\TokenpassAPI', function($app) {
            return $this->mock_tokenpass_api;
        });

        return $this->called_api_methods;
    }


}
