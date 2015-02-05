<?php

namespace Swapbot\Http\Controllers\API\BalanceRefresh;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\UpdateBotBalances;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Http\Controllers\API\Helpers\APIControllerHelper;
use Swapbot\Repositories\BotRepository;

class BalanceRefreshController extends APIController {

    /**
     * Updates the bot balances
     *
     * @param  int  $botuuid
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @return Response
     */
    public function refresh($botuuid, Guard $auth, BotRepository $repository, APIControllerHelper $api_helper)
    {
        $resource = $api_helper->requireResourceOwnedByUser($botuuid, $auth->getUser(), $repository);

        $this->dispatch(new UpdateBotBalances($resource));

        // return an empty 204 response (http://httpstatus.es/204)
        return new Response('', 204);
    }



}
