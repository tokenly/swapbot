<?php namespace Swapbot\Http\Controllers;

use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Http\Requests;
use Swapbot\Models\Formatting\FormattingHelper;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\UserRepository;
use Exception;

class PublicBotController extends Controller {

    function __construct(UserRepository $user_repository, BotRepository $bot_repository) {
        $this->user_repository = $user_repository;
        $this->bot_repository  = $bot_repository;

        $this->middleware('tls');
    }

    /**
     * Show the bot HTML
     *
     * @return Response
     */
    public function showBot($username, $slug_or_id, BotRepository $bot_repository)
    {

        // try UUID first
        if ($this->looksLikeAUUID($slug_or_id)) {
            $bot_uuid = $slug_or_id;
            list($user, $bot) = $this->requireUserAndBot($username, $bot_uuid, null);

            // redirect to bot
            if (strlen($bot['url_slug']) AND $bot['url_slug'] != $bot_uuid) {
                // redirect to the slug version
                return redirect($bot->getPublicBotURL(), 301);
            }
        } else {
            // lookup by slug
            $bot_slug = $slug_or_id;
            list($user, $bot) = $this->requireUserAndBot($username, null, $bot_slug);
        }

        return view('public.bot', [
            'bot'               => $bot,
            'botRobohashUrl'    => $bot->getRobohashURL(),
        ]);
    }

    public function redirectToCanonicalBotURL($username, $bot_uuid) {
        list($user, $bot) = $this->requireUserAndBot($username, $bot_uuid, null);

        return redirect($bot->getPublicBotURL(), 301);
    }

    ////////////////////////////////////////////////////////////////////////
    
    

    protected function looksLikeAUUID($uuid) {
        return Uuid::isValid($uuid);
    }

    protected function requireUserAndBot($username, $bot_uuid=null, $bot_slug=null) {

        // get user by username
        $user = $this->user_repository->findByUsername($username);
        if (!$user) { throw new HttpResponseException(new Response("This user was not found.", 404)); }

        // find the bot
        $bot = null;
        if ($bot_uuid !== null AND $bot_slug === null) {
            $bot = $this->bot_repository->findByUuidAndUserID($bot_uuid, $user['id']);
        } else if ($bot_uuid === null AND $bot_slug !== null) {
            $bot = $this->bot_repository->findBySlugAndUserID($bot_slug, $user['id']);
        }
        if (!$bot) { throw new HttpResponseException(new Response("This bot was not found.", 404)); }

        return [$user, $bot];
    }


}
