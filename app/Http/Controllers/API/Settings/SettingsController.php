<?php

namespace Swapbot\Http\Controllers\API\Settings;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Swapbot\Events\SettingWasChanged;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Http\Requests;
use Swapbot\Http\Requests\Settings\Transformers\SettingTransformer;
use Swapbot\Http\Requests\Settings\Validators\CreateSettingValidator;
use Swapbot\Http\Requests\Settings\Validators\UpdateSettingValidator;
use Swapbot\Models\Setting;
use Swapbot\Models\User;
use Swapbot\Repositories\SettingRepository;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class SettingsController extends APIController {


    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  SettingRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function index(Guard $auth, SettingRepository $repository, APIControllerHelper $api_helper)
    {
        $auth_user = $auth->getUser();

        // all users for this user
        $resources = $repository->findAll();
        $resources = $this->resourcesThatAreAuthorized($resources, $auth_user);

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
    public function store(Request $request, Guard $auth, CreateSettingValidator $validator, SettingTransformer $transformer, SettingRepository $repository, APIControllerHelper $api_helper)
    {
        $auth_user = $auth->getUser();

        // create the settings
        try {
            // transform
            $create_vars = $transformer->santizeAttributes($request->all(), $validator->getRules());

            // validate
            $validator->validate($create_vars);

            // require permissions
            $this->requirePermissionsForSettingName($auth_user, $create_vars['name'], $api_helper);

            // create a settings
            $resource = $repository->createOrUpdate($create_vars['name'], $create_vars['value']);

            // fire event
            $this->fireSettingsChangeEvent($resource, 'create');

        } catch (ValidationException $e) {
            // handle validation errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->errors()->all(), 422));

        } catch (InvalidArgumentException $e) {
            // handle invalid argument errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->getMessage(), 422));
        }

        // return the model id
        return $api_helper->transformResourceForOutput($resource);
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
        $resource = $api_helper->requireResource($id, $repository);
        $this->requirePermissionsForSettingName($auth_user, $resource['name'], $api_helper);
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
        $resource = $api_helper->requireResource($id, $repository);
        $this->requirePermissionsForSettingName($auth_user, $resource['name'], $api_helper);

        // update settings
        try {
            // transform
            $update_vars = $transformer->santizeAttributes($request->all(), $validator->getRules());

            // validate
            $validator->validate($update_vars);
            Log::debug('$update_vars='.json_encode($update_vars, 192));

            $repository->update($resource, $update_vars);

            // fire event
            $this->fireSettingsChangeEvent($resource, 'update');

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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @param  Guard               $auth
     * @param  SettingRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function destroy($id, Guard $auth, SettingRepository $repository, APIControllerHelper $api_helper)
    {
        $auth_user = $auth->getUser();
        $resource = $api_helper->requireResource($id, $repository);
        $this->requirePermissionsForSettingName($auth_user, $resource['name'], $api_helper);

        // issue a delete user command
        $repository->delete($resource);

        $this->fireSettingsChangeEvent($resource, 'delete');

        // return a 204 response
        return new Response('', 204);
    }


    // ------------------------------------------------------------------------

    protected function requirePermissionsForSettingName(User $user, $setting_name, $api_helper) {
        Log::debug("requirePermissionsForSettingName \$setting_name=".json_encode($setting_name, 192));
        $permission_spec = $this->permissionSpecBySettingName($setting_name);
        if (!$user->hasPermission($permission_spec['permission'])) {
            // permissions not found
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors("This user is not authorized to manage {$permission_spec['descPlural']}", 403));
        }
    }

    protected function permissionSpecBySettingName($setting_name) {
        switch ($setting_name) {
            case 'globalAlert':
                return [
                    'permission' => 'manageGlobalAlert',
                    'descSingular' => 'global alert',
                    'descPlural' => 'global alerts',
                ];
        }

        return [
            'permission' => 'manageSettings',
            'descSingular' => 'setting',
            'descPlural' => 'settings',
        ];
    }

    protected function resourcesThatAreAuthorized($collection, User $user) {
        return $collection->filter(function ($resource) use ($user) {
            $permission_spec = $this->permissionSpecBySettingName($resource['name']);
            return $user->hasPermission($permission_spec['permission']);
        });
    }

    protected function fireSettingsChangeEvent(Setting $setting, $event_type) {
        Event::fire(new SettingWasChanged($setting, $event_type));
    }

}
