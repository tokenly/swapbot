<?php

namespace Swapbot\Http\Controllers\API\Base;

use Exception;
use Swapbot\Http\Controllers\Controller;

class APIController extends Controller {

    protected $protected = true;

    public function __construct() {
        $this->addMiddleware();
    }

    public function addMiddleware() {
        // catch all errors and return a JSON response
        $this->middleware('api.catchErrors');

        if ($this->protected) {
            // require hmacauth middleware for all API requests
            $this->middleware('api.protectedAuth');
        }
    }

}
