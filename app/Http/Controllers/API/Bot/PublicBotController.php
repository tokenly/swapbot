<?php

namespace Swapbot\Http\Controllers\API\Bot;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Repositories\BotRepository;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class PublicBotController extends APIController {

    protected $protected = false;

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function show($id, BotRepository $repository, APIControllerHelper $api_helper)
    {
        $resource = $api_helper->requireResource($id, $repository);
        return $api_helper->transformResourceForOutput($resource, 'public');
    }

}
