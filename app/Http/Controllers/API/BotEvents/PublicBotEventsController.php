<?php

namespace Swapbot\Http\Controllers\API\BotEvents;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\Logger\OutputTransformer\Facade\BotEventOutputTransformer;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class PublicBotEventsController extends APIController {

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
    public function index($botuuid, Request $request, BotRepository $bot_repository, BotEventRepository $bot_event_repository, APIControllerHelper $api_helper)
    {
        // get the bot
        $bot = $api_helper->requireResource($botuuid, $bot_repository);

        // get all events fot this bot
        $resources = $bot_event_repository->findByBotId($bot['id']);

        // format for API
        return $api_helper->transformResourcesForOutput($resources);
    }


    public function swapsEventStreamIndex($botuuid, Request $request, BotRepository $bot_repository, BotEventRepository $bot_event_repository, APIControllerHelper $api_helper)
    {
        $one_event_per_swap = true;
        if ($request->input('allevents')) { $one_event_per_swap = false; }

        // get the bot
        $bot = $api_helper->requireResource($botuuid, $bot_repository);

        if ($one_event_per_swap) {
            // get swapstream events for this bot
            //  but only the latest swap event for each swap
            $resources = $bot_event_repository->findLatestSwapStreamEventsByBotId($bot['id'], $this->buildSwapStreamFilter($request));

            // add the receipt to each swap event if needed
            $out = [];
            foreach ($resources as $bot_event) {
                $bot_event_output = $bot_event->serializeForAPI();
                if (BotEventOutputTransformer::isMissingStandardSwapEventData($bot_event_output)) {
                    $bot_event_output = BotEventOutputTransformer::addMissingStandardSwapEventData($bot_event->swap, $bot_event_output);
                }
                $out[] = $bot_event_output;
            }

            // format for API
            return $api_helper->buildJSONResponse($out);
        }

        // get all swap events, including multiple events for the same swap
        $resources = $bot_event_repository->findAllSwapStreamEventsByBotId($bot['id'], $this->buildSwapStreamFilter($request));

        return $api_helper->transformResourcesForOutput($resources);
    }


    public function botEventStreamIndex($botuuid, Request $request, BotRepository $bot_repository, BotEventRepository $bot_event_repository, APIControllerHelper $api_helper)
    {
        // get the bot
        $bot = $api_helper->requireResource($botuuid, $bot_repository);

        // get all events fot this bot
        $resources = $bot_event_repository->findBotStreamEventsByBotId($bot['id'], $this->buildFilter($request));

        // format for API
        return $api_helper->transformResourcesForOutput($resources);
    }

    protected function buildFilter(Request $request) {
        return IndexRequestFilter::createFromRequest($request, $this->getFilterDefinition());
    }

    protected function buildSwapStreamFilter(Request $request) {
        $definition = $this->getFilterDefinition();

        // need to transform uuid into id before filtering
        // $definition['fields']['swapId'] = ['field' => 'swap_id'];

        return IndexRequestFilter::createFromRequest($request, $definition);
    }

    protected function getFilterDefinition() {
        return [
            'fields' => [
                'serial' => ['sortField' => 'serial', 'defaultSortDirection' => 'asc'],
            ],
            'defaults' => ['sort' => 'serial'],
        ];
    }

}
