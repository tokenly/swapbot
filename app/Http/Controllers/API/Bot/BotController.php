<?php

namespace Swapbot\Http\Controllers\API\Bot;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Commands\CreateBot;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Http\Controllers\API\Helpers\APIControllerHelper;
use Swapbot\Http\Requests;
use Swapbot\Repositories\BotRepository;

class BotController extends APIController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(BotRepository $repository, Guard $auth, APIControllerHelper $api_helper)
	{
        Log::debug('index');

		// all bots for this user
		$resources = $repository->findByUser($auth->getUser());
		Log::debug('$resources='.json_encode(iterator_to_array($resources), 192));

		// format for API
		return $api_helper->transformResourcesForOutput($resources);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function store(Guard $auth, Request $request)
	{
		$attributes = $request->all();

		// create a UUID
		$uuid = Uuid::uuid4()->toString();
		$attributes['uuid'] = $uuid;

		// add the user
		$attributes['user_id'] = $auth->getUser()['id'];

		// issue a create user command
		try {
			$this->dispatch(new CreateBot(['attributes' => $attributes]));
		} catch (ValidationException $e) {
			// handle validation errors
			if (!$resource) { return new JsonResponse(['errors' => $e->errors()->all()], 422); }
		}

		// return the model id
		return ['id' => $uuid];
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

}
