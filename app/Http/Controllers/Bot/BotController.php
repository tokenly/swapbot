<?php

namespace Swapbot\Http\Controllers\Bot;

use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Response;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Http\Requests\Bot\EditBotRequest;
use Swapbot\Repositories\BotRepository;

class BotController extends Controller {

    public function __construct()
    {
        // auth required
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getIndex()
    {
        //
        return 'List of Bots!';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function getEdit()
    {
        return view('bot.edit');
    }

    public function postEdit(EditBotRequest $request, BotRepository $repository, Guard $auth)
    {
        // get user
        $user = $auth->getUser();
        if (!$user) { throw new Exception("User not found", 1); }

        // create the bot
        $new_vars = $repository->compactAssetAttributes($request->getFilteredData());
        $new_vars['user_id'] = $user['id'];
        $new_model = $repository->create($new_vars);

        // redirect
        return redirect('/bot/show/'.$new_model['uuid']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $uuid
     * @return Response
     */
    public function getShow($uuid, BotRepository $repository, Guard $auth)
    {
        // get user
        $user = $auth->getUser();
        if (!$user) { throw new Exception("User not found", 1); }

        // get the bot
        $bot = $repository->findByUUID($uuid);
        if (!$bot) { throw new Exception("Bot not found", 1); }

        // make sure the bot belongs to the user
        if ($bot['user_id'] != $user['id']) {
            return new Response('Access denied', 403);
        }
        

        return $bot->serializeForAPI();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $uuid
     * @return Response
     */
    public function edit($uuid)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $uuid
     * @return Response
     */
    public function update($uuid)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $uuid
     * @return Response
     */
    public function destroy($uuid)
    {
        //
    }

}
