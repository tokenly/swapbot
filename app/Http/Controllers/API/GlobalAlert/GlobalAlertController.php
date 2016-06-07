<?php

namespace Swapbot\Http\Controllers\API\GlobalAlert;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Http\Requests;
use Swapbot\Repositories\SettingRepository;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class GlobalAlertController extends APIController {



    /**
     * create a new resource.
     *
     * @param  Request             $request
     * @param  Guard               $auth
     * @return Response
     */
    public function store(Request $request, Guard $auth, CreateSettingValidator $validator, SettingTransformer $transformer, SettingRepository $repository, APIControllerHelper $api_helper)
    {
        $auth_user = $auth->getUser();
        if (!$auth_user->hasPermission('manageGlobalAlert')) {
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors("This user is not authorized to manage settings", 403));
        }

        // issue a create settings command
        try {
            // transform
            $create_vars = $transformer->santizeAttributes($request->all(), $validator->getRules());

            // validate
            $validator->validate($create_vars);

            // create a settings
            $settings = $repository->createOrUpdate($create_vars['name'], $create_vars['value']);

        } catch (ValidationException $e) {
            // handle validation errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->errors()->all(), 422));

        } catch (InvalidArgumentException $e) {
            // handle invalid argument errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->getMessage(), 422));
        }

        // return the model id
        return $api_helper->transformResourceForOutput($settings);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  Guard               $auth
     * @param  SettingRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function show($id, Guard $auth, SettingRepository $repository, APIControllerHelper $api_helper)
    {
        $auth_user = $auth->getUser();
        if (!$auth_user->hasPermission('manageGlobalAlert')) {
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors("This user is not authorized to manage settings", 403));
        }

        $resource = $api_helper->requireResource($id, $repository);
        return $api_helper->transformResourceForOutput($resource);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @param  Request             $request
     * @param  Guard               $auth
     * @param  SettingRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function update($id, Request $request, Guard $auth, UpdateSettingValidator $validator, SettingTransformer $transformer, SettingRepository $repository, APIControllerHelper $api_helper)
    {
        $auth_user = $auth->getUser();
        if (!$auth_user->hasPermission('manageGlobalAlert')) {
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors("This user is not authorized to manage settings", 403));
        }

        $resource = $api_helper->requireResource($id, $repository);

        // update settings
        try {
            // transform
            $update_vars = $transformer->santizeAttributes($request->all(), $validator->getRules());

            // validate
            $validator->validate($update_vars);
            Log::debug('$update_vars='.json_encode($update_vars, 192));

            $repository->update($resource, $update_vars);
        } catch (InvalidArgumentException $e) {
            // handle invalid argument errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->getMessage(), 422));

        } catch (ValidationException $e) {
            // handle validation errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->errors()->all(), 422));
        }

        // return a 204 response
        return new Response('', 204);
    }

}
