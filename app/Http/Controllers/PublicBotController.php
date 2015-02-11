<?php namespace Swapbot\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Http\Requests;
use Swapbot\Repositories\BotRepository;

class PublicBotController extends Controller {

	/**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */
    public function showBot($userid, $botid, BotRepository $bot_repository)
    {
        // $bot = $bot_repository->findByUuid($botid);

        return view('public.bot', [
            // 'bot'       => $bot,
            'pusherUrl' => Config::get('tokenlyPusher.clientUrl'),
        ]);
    }

}
