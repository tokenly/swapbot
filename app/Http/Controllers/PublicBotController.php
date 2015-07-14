<?php namespace Swapbot\Http\Controllers;

use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Http\Requests;
use Swapbot\Models\Formatting\SwapFormatter;
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
    public function showBot($username, $botid, BotRepository $bot_repository, SwapFormatter $swap_formatter)
    {
        list($user, $bot) = $this->requireUserAndBot($username, $botid);

        $pusher_url = Config::get('tokenlyPusher.clientUrl');
        return view('public.bot', [
            'bot'               => $bot,
            'quotebot'          => [
                'url'      => rtrim(Config::get('quotebot.connection_url'), '/'),
                'apiToken' => Config::get('quotebot.api_token'),
            ],
            'swapFormatter'     => $swap_formatter,
            'pusherUrl'         => $pusher_url,
            'quotebotPusherUrl' => rtrim(env('QUOTEBOT_PUSHER_CLIENT_URL', $pusher_url), '/'),
            'env'               => app()->environment(),

            'analyticsId'       => env('GOOGLE_ANALYTICS_ID'),
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
