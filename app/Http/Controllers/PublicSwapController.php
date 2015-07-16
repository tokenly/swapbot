<?php namespace Swapbot\Http\Controllers;

use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Models\Formatting\FormattingHelper;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Repositories\UserRepository;

class PublicSwapController extends Controller {

    function __construct(UserRepository $user_repository, SwapRepository $swap_repository) {
        $this->user_repository = $user_repository;
        $this->swap_repository  = $swap_repository;
    }

    /**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */
    public function showSwap($username, $swapid, SwapRepository $swap_repository, FormattingHelper $formatting_helper)
    {
        list($user, $bot, $swap) = $this->requireUserAndSwap($username, $swapid);

        return view('public.swap-details', [
            'swap'           => $swap,
            'bot'            => $bot,
            'botRobohashUrl' => $bot->getRobohashURL(),
            'strategy'       => $swap->getSwapConfigStrategy(),
        ]);
    }


    protected function requireUserAndSwap($username, $swapid) {

        // get user by username
        $user = $this->user_repository->findByUsername($username);
        if (!$user) { throw new HttpResponseException(new Response("This user was not found.", 404)); }

        // find the swap
        $swap = $this->swap_repository->findByUuid($swapid);
        if (!$swap) { throw new HttpResponseException(new Response("This swap was not found.", 404)); }

        $bot = $swap->bot;

        // make sure the swap belongs to the user
        if ($user['id'] != $bot['user_id']) {
            throw new HttpResponseException(new Response("This swap was not found for this user.", 404));
        }

        return [$user, $bot, $swap];
    }



}
