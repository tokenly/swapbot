<?php

namespace Swapbot\Http\Controllers\API\BotEvents;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\BotRepository;

class BotEventsController extends APIController {

    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function index($botuuid, Guard $auth, BotRepository $bot_repository, BotEventRepository $bot_event_repository, APIControllerHelper $api_helper)
    {
        $user = $auth->getUser();

        // get the bot
        $bot = $api_helper->requireResourceOwnedByUser($botuuid, $user, $bot_repository);

        // get all events fot this bot
        $resources = $bot_event_repository->findByBotId($bot['id']);

        // format for API
        return $api_helper->transformResourcesForOutput($resources);
    }



}
