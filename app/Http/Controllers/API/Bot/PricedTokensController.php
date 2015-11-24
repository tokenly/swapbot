<?php

namespace Swapbot\Http\Controllers\API\Bot;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Swap\Settings\Facade\Settings;
use Swapbot\Util\PricedTokens\PricedTokensHelper;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class PricedTokensController extends APIController {

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getPricedTokens(APIControllerHelper $api_controller_helper, PricedTokensHelper $priced_tokens_helper) {

        $tokens_list = $priced_tokens_helper->getAllPricedTokensList();

        return $api_controller_helper->buildJSONResponse($tokens_list);
    }


}
