<?php

namespace Swapbot\Http\Controllers\API\Swap;

use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\SwapRepository;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class PublicSwapController extends APIController {

    protected $protected = false;

    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function index($botuuid, BotRepository $bot_repository, SwapRepository $swap_repository, APIControllerHelper $api_helper)
    {
        // get the bot
        $bot = $api_helper->requireResource($botuuid, $bot_repository);

        // get all swaps fot this bot
        $swaps = $swap_repository->findByBotId($bot['id']);

        // format for API
        return $api_helper->transformResourcesForOutput($swaps);
    }


}
