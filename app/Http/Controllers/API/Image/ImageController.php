<?php

namespace Swapbot\Http\Controllers\API\Image;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Models\Image;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\ImageRepository;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class ImageController extends APIController {

    /**
     * create a new resource.
     *
     * @param  Request             $request
     * @param  Guard               $auth
     * @return Response
     */
    public function store(Guard $auth, Request $request, BotRepository $bot_repository, ImageRepository $image_repository, APIControllerHelper $api_helper)
    {
        $attributes = $request->all();

        $user = $auth->getUser();

        // issue a create bot command
        try {
            // check image file exists
            if (!isset($attributes['image']) OR !strlen($attributes['image'])) { throw new InvalidArgumentException("The image file was missing."); }

            // create an image
            $image = $image_repository->createForUser($user, $attributes['image']);

        } catch (InvalidArgumentException $e) {
            // handle invalid argument errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->getMessage(), 422));
        }

        // return the model id
        return $api_helper->transformResourceForOutput($image);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  Guard               $auth
     * @param  ImageRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function show($id, Guard $auth, ImageRepository $repository, APIControllerHelper $api_helper)
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
     * @param  ImageRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function update($id, Request $request, Guard $auth, ImageRepository $repository, APIControllerHelper $api_helper)
    {
        $resource = $api_helper->requireResourceOwnedByUser($id, $auth->getUser(), $repository);

        // get the update attributes
        $attributes = $request->all();

        // issue an update bot command
        try {
            $this->dispatch(new UpdateImage($resource, $attributes));
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
     * @param  ImageRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function destroy($id, Guard $auth, ImageRepository $repository, APIControllerHelper $api_helper)
    {
        $resource = $api_helper->requireResourceOwnedByUser($id, $auth->getUser(), $repository);

        // issue a delete bot command
        $this->dispatch(new DeleteImage($resource));

        // return a 204 response
        return new Response('', 204);
    }

}
