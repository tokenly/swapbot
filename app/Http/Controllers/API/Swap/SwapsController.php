<?php

namespace Swapbot\Http\Controllers\API\Swap;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Models\Swap;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\SwapRepository;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class SwapsController extends APIController {

    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function index(Request $request, Guard $auth, SwapRepository $swap_repository, APIControllerHelper $api_helper)
    {
        $params = $request->all();

        // require the viewSwaps permission
        $api_helper->requirePermission($auth->getUser(), 'viewSwaps', 'view all swaps');

        // get all swaps fot this bot
        $out = [];

        $resources = $swap_repository->findAllWithBots($params, isset($params['sort']) ? $params['sort'] : null);

        // format for API
        return $api_helper->transformResourcesForOutput($resources, 'with_bot');
    }


}
