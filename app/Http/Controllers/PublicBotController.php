<?php namespace Swapbot\Http\Controllers;

use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Http\Requests;
use Swapbot\Models\Formatting\FormattingHelper;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\UserRepository;

class PublicBotController extends Controller {

    function __construct(UserRepository $user_repository, BotRepository $bot_repository) {
        $this->user_repository = $user_repository;
        $this->bot_repository  = $bot_repository;
    }

    /**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */
    public function showBot($username, $botid, BotRepository $bot_repository)
    {
        list($user, $bot) = $this->requireUserAndBot($username, $botid);

        return view('public.bot', [
            'bot'               => $bot,
            'botRobohashUrl'    => $bot->getRobohashURL(),
        ]);
    }


    protected function requireUserAndBot($username, $botid) {

        // get user by username
        $user = $this->user_repository->findByUsername($username);
        if (!$user) { throw new HttpResponseException(new Response("This user was not found.", 404)); }

        // find the bot
        $bot = $this->bot_repository->findByUuidAndUserID($botid, $user['id']);
        if (!$bot) { throw new HttpResponseException(new Response("This bot was not found.", 404)); }

        return [$user, $bot];
    }



}
