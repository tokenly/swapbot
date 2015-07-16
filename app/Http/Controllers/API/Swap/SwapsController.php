<?php

namespace Swapbot\Http\Controllers\API\Swap;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Models\Swap;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\SwapRepository;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
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
        // require the viewSwaps permission
        $api_helper->requirePermission($auth->getUser(), 'viewSwaps', 'view all swaps');

        // get all swaps fot this bot
        $resources = $swap_repository->findAllWithBots($this->buildFilter($request, $swap_repository));

        // format for API
        return $api_helper->transformResourcesForOutput($resources, 'with_bot');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function show($swapuuid, Guard $auth, SwapRepository $repository, APIControllerHelper $api_helper)
    {
        if ($auth->getUser()->hasPermission('viewSwaps')) {
            $resource = $api_helper->requireResource($swapuuid, $repository);

        } else {
            $resource = $api_helper->requireResourceOwnedByUser($swapuuid, $auth->getUser(), $repository);
            
        }
        return $api_helper->transformResourceForOutput($resource, 'with_bot');
    }


    protected function buildFilter(Request $request, SwapRepository $swap_repository) {
        return IndexRequestFilter::createFromRequest($request, $swap_repository->buildFindAllWithBotsFilterDefinition());
    }


}
