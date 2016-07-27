<?php

namespace Swapbot\Http\Controllers\API\Swap;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\SwapRepository;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class PublicSwapController extends APIController {

    protected $protected = false;

    public function addMiddleware() {
        parent::addMiddleware();

        // allow cors
        $this->middleware('cors');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function index($botuuid, Request $request, BotRepository $bot_repository, SwapRepository $swap_repository, APIControllerHelper $api_helper)
    {
        // get the bot
        $bot = $api_helper->requireResource($botuuid, $bot_repository);

        // get all swaps fot this bot
        $swaps = $swap_repository->findByBotId($bot['id'], $this->buildFilter($request, $swap_repository));

        // format for API
        return $api_helper->transformResourcesForOutput($swaps);
    }

    protected function buildFilter(Request $request, SwapRepository $swap_repository) {
        return IndexRequestFilter::createFromRequest($request, $swap_repository->buildFilterDefinition());
    }

}
