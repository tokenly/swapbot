<?php

namespace Swapbot\Http\Controllers\API\Bot;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Commands\ActivateBot;
use Swapbot\Commands\CreateBot;
use Swapbot\Commands\DeleteBot;
use Swapbot\Commands\UpdateBot;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Http\Controllers\API\Helpers\APIControllerHelper;
use Swapbot\Http\Requests;
use Swapbot\Repositories\BotRepository;

class BotController extends APIController {

    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function index(Guard $auth, BotRepository $repository, APIControllerHelper $api_helper)
    {
        // all bots for this user
        $resources = $repository->findByUser($auth->getUser());
        // Log::debug('$resources='.json_encode(iterator_to_array($resources), 192));

        // format for API
        return $api_helper->transformResourcesForOutput($resources);
    }

    /**
     * create a new resource.
     *
     * @param  Request             $request
     * @param  Guard               $auth
     * @return Response
     */
    public function store(Request $request, Guard $auth, BotRepository $repository, APIControllerHelper $api_helper)
    {
        $attributes = $request->all();

        // create a UUID
        $uuid = Uuid::uuid4()->toString();
        $attributes['uuid'] = $uuid;

        // add the user
        $attributes['user_id'] = $auth->getUser()['id'];

        // issue a create bot command
        try {
            // create a bot
            $this->dispatch(new CreateBot($attributes));

            // activate the bot
            $bot = $repository->findByUuid($uuid);
            $this->dispatch(new ActivateBot($bot));
            
            // reload the bot again
            $bot = $repository->findByUuid($uuid);


        } catch (ValidationException $e) {
            // handle validation errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->errors()->all(), 422));

        } catch (InvalidArgumentException $e) {
            // handle invalid argument errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->getMessage(), 422));
        }

        // return the model id
        return $api_helper->transformResourceForOutput($bot);
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
    public function show($id, Guard $auth, BotRepository $repository, APIControllerHelper $api_helper)
    {
        $resource = $api_helper->requireResourceOwnedByUser($id, $auth->getUser(), $repository);
        return $api_helper->transformResourceForOutput($resource);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @param  Request             $request
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function update($id, Request $request, Guard $auth, BotRepository $repository, APIControllerHelper $api_helper)
    {
        $resource = $api_helper->requireResourceOwnedByUser($id, $auth->getUser(), $repository);

        // get the update attributes
        $attributes = $request->all();

        // issue an update bot command
        try {
            $this->dispatch(new UpdateBot($resource, $attributes));
        } catch (ValidationException $e) {
            // handle validation errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->errors()->all(), 422));
        }

        // return a 204 response
        return new Response('', 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function destroy($id, Guard $auth, BotRepository $repository, APIControllerHelper $api_helper)
    {
        $resource = $api_helper->requireResourceOwnedByUser($id, $auth->getUser(), $repository);

        // issue a delete bot command
        $this->dispatch(new DeleteBot($resource));

        // return a 204 response
        return new Response('', 204);
    }

}
