<?php

namespace Swapbot\Http\Controllers\Bot;

use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Http\Requests\Bot\ActivateBotRequest;
use Swapbot\Http\Requests\Bot\EditBotRequest;
use Swapbot\Models\Bot;
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
    public function index()
    {
        Log::debug('index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function getEdit($uuid, BotRepository $repository, Guard $auth)
    {
        if ($uuid === 'new') {
            $bot = new Bot();
        } else {
            $bot = $this->getBotForUser($uuid, $repository, $auth);
        }
        return view('bot.edit', ['bot' => $bot]);
    }

    public function postEdit($uuid, EditBotRequest $request, BotRepository $repository, Guard $auth)
    {

        $bot_vars = $repository->compactSwapAttributes($request->getFilteredData());

        // create the bot
        if ($uuid == 'new') {
            $user = $auth->getUser();
            if (!$user) { throw new Exception("User not found", 1); }
            $bot_vars['user_id'] = $user['id'];
            $bot_model = $repository->create($bot_vars);
        } else {
            $existing_bot = $this->getBotForUser($uuid, $repository, $auth);
            $bot_model = $repository->update($existing_bot, $bot_vars);
        }

        // redirect
        return redirect('/bot/show/'.$bot_model['uuid']);
    }

    public function getActivate($uuid, BotRepository $repository, Guard $auth)
    {
        $bot = $this->getBotForUser($uuid, $repository, $auth);
        return view('bot.activate', ['bot' => $bot]);
    }

    public function postActivate($uuid, ActivateBotRequest $request, BotRepository $repository, Guard $auth)
    {
        $existing_bot = $this->getBotForUser($uuid, $repository, $auth);

        // activate it
        $repository->update($existing_bot, ['active' => true]);

        // redirect
        return redirect('/bot/show/'.$existing_bot['uuid']);
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
        $bot = $this->getBotForUser($uuid, $repository, $auth);

        return view('bot.show', ['bot' => $bot->serializeForAPI()]);
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


    protected function getBotForUser($uuid, $repository, $auth) {
        // get user
        $user = $auth->getUser();
        if (!$user) { throw new Exception("User not found", 1); }

        // get the bot
        $bot = $repository->findByUUID($uuid);
        if (!$bot) { throw new Exception("Bot not found", 1); }

        // make sure the bot belongs to the user
        if ($bot['user_id'] != $user['id']) {
            throw new HttpResponseException(new Response('Forbidden', 403));
        }
        
        return $bot;
    }

}
