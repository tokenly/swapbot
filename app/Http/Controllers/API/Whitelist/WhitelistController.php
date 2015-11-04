<?php

namespace Swapbot\Http\Controllers\API\Whitelist;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LinusU\Bitcoin\AddressValidator;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Events\WhitelistWasDeleted;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Repositories\WhitelistRepository;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class WhitelistController extends APIController {

    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  WhitelistRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function index(Request $request, Guard $auth, WhitelistRepository $repository, APIControllerHelper $api_helper)
    {
        // all whitelists for this user
        $resources = $repository->findByUser($auth->getUser(), $this->buildFilter($request, $repository));

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
    public function store(Request $request, Guard $auth, WhitelistRepository $repository, APIControllerHelper $api_helper)
    {
        // create the whitelist
        try {
            // validate
            $attributes = $this->validateAttributesForAPI($request->all(), [
                'name' => 'required|max:255',
                'data' => 'required',
            ]);

            $user = $auth->getUser();

            // create a UUID
            $uuid = Uuid::uuid4()->toString();
            $attributes['uuid'] = $uuid;

            // add the user
            $attributes['user_id'] = $auth->getUser()['id'];

            // validate data
            $attributes['data'] = $this->cleanAndValidateWhitelist($attributes['data']);

            // create a whitelist
            $whitelist = $repository->create($attributes);

        } catch (ValidationException $e) {
            // handle validation errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->errors()->all(), 422));

        } catch (InvalidArgumentException $e) {
            // handle invalid argument errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->getMessage(), 422));
        }

        // return the model id
        return $api_helper->transformResourceForOutput($whitelist);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  Guard               $auth
     * @param  WhitelistRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function show($id, Guard $auth, WhitelistRepository $repository, APIControllerHelper $api_helper)
    {
        $resource = $api_helper->requireResourceOwnedByUserOrWithPermssion($id, $auth->getUser(), $repository, 'viewBots');
        $output = $api_helper->transformResourceForOutput($resource);
        return $output;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @param  Request             $request
     * @param  Guard               $auth
     * @param  WhitelistRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function update($id, Request $request, Guard $auth, WhitelistRepository $repository, APIControllerHelper $api_helper)
    {
        $user = $auth->getUser();
        $resource = $api_helper->requireResourceOwnedByUser($id, $user, $repository);

        // update the whitelist
        try {
            // validate
            $attributes = $this->validateAttributesForAPI($request->all(), [
                'name' => 'sometimes|required|max:255',
                'data' => 'sometimes|required',
            ]);

            // validate data
            if (isset($attributes['data'])) {
                $attributes['data'] = $this->cleanAndValidateWhitelist($attributes['data']);
            }

            // require something
            if (!isset($attributes['name']) AND !isset($attributes['data'])) { throw new InvalidArgumentException("No name or data was provided to update."); }

            $repository->update($resource, $attributes);
        } catch (ValidationException $e) {
            // handle validation errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->errors()->all(), 422));
        } catch (InvalidArgumentException $e) {
            // handle invalid argument errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->getMessage(), 422));
        }

        // return a 204 response
        return new Response('', 204);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @param  Guard               $auth
     * @param  WhitelistRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function destroy($id, Guard $auth, WhitelistRepository $repository, APIControllerHelper $api_helper)
    {
        $resource = $api_helper->requireResourceOwnedByUser($id, $auth->getUser(), $repository);

        DB::transaction(function() use ($repository, $resource) {
            // delete the whitelist
            $repository->delete($resource);

            // fire a deleted event
            Event::fire(new WhitelistWasDeleted($resource));
        });

        // return a 204 response
        return new Response('', 204);
    }


    protected function cleanAndValidateWhitelist($data) {
        Log::debug("\$data=".json_encode($data, 192));
        if (!is_array($data)) { throw new InvalidArgumentException("Whitelist data was not valid"); }

        $errors = [];
        $addresses = [];
        foreach($data as $offset => $address) {
            if (AddressValidator::isValid($address)) {
                $addresses[] = $address;
            } else {
                // allow for a header row (which we will ignore)
                if ($offset > 0) {
                    $errors[] = "The address $address was not valid in row ".($offset + 1)."";
                }
            }
        }

        if ($errors) {
            $error_text = implode(", ", $errors);
            if (count($errors) > 12) {
                $error_text = implode(", ", array_slice($errors, 0, 10));
                $error_text .= ", and ".(count($errors) - 10)." other errors";
                
            }
            throw new InvalidArgumentException($error_text);
        }

        if (!$addresses) {
            throw new InvalidArgumentException("No addresses were found");
        }

        return $addresses;
    }

    protected function buildFilter(Request $request, WhitelistRepository $repository) {
        return IndexRequestFilter::createFromRequest($request, $repository->buildFilterDefinition());
    }

}
