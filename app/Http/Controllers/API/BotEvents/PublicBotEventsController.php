<?php

namespace Swapbot\Http\Controllers\API\BotEvents;

use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\BotRepository;

class PublicBotEventsController extends APIController {

    protected $protected = false;

    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function index($botuuid, BotRepository $bot_repository, BotEventRepository $bot_event_repository, APIControllerHelper $api_helper)
    {
        // get the bot
        $bot = $api_helper->requireResource($botuuid, $bot_repository);

        // get all events fot this bot
        $resources = $bot_event_repository->findByBotId($bot['id']);

        // format for API
        return $api_helper->transformResourcesForOutput($resources);
    }


    public function swapsEventStreamIndex($botuuid, BotRepository $bot_repository, BotEventRepository $bot_event_repository, APIControllerHelper $api_helper)
    {
        // get the bot
        $bot = $api_helper->requireResource($botuuid, $bot_repository);

        // get all events fot this bot
        $resources = $bot_event_repository->findSwapsEventStreamByBotId($bot['id']);

        // format for API
        return $api_helper->transformResourcesForOutput($resources);
    }


    public function botEventStreamIndex($botuuid, BotRepository $bot_repository, BotEventRepository $bot_event_repository, APIControllerHelper $api_helper)
    {
        // get the bot
        $bot = $api_helper->requireResource($botuuid, $bot_repository);

        // get all events fot this bot
        $resources = $bot_event_repository->findBotEventStreamByBotId($bot['id']);

        // format for API
        return $api_helper->transformResourcesForOutput($resources);
    }



}
