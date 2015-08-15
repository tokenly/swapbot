<?php

namespace Swapbot\Http\Controllers\Account;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Models\User;
use Swapbot\Repositories\UserRepository;

class AccountController extends Controller
{

    public function login() {
        $user = Auth::user();
        if ($user) {
            // redirect to credentials check
            return redirect('/account/credentialscheck');
        }

        return view('public.account.login', ['user' => $user]);
    }

    public function credentialsCheck() {
        $user = Auth::user();
        if (!$user) { return redirect('/account/login'); }

        return 
            Response::view('public.account.credentialscheck', ['user' => $user])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }

    public function welcome() {
        $user = Auth::user();
        if (!$user) { return redirect('/account/login'); }

        return view('public.account.welcome', ['user' => $user]);
    }

    public function logout() {
        Auth::logout();
        return view('public.account.loggedout', []);
    }

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return Response
     */
    public function handleProviderCallback(Request $request, UserRepository $user_repository)
    {
        // check for error
        $error_code = $request->get('error');
        if ($error_code) {
            if ($error_code == 'access_denied') {
                $error_description = 'Access was denied.';

            } else {
                $error_description = $request->get('error_description');
            }
            return view('public.oauth.authorization-failed', ['error_msg' => $error_description]);
        }


        Log::debug("handleProviderCallback called");

        try {
            $oauth_user = Socialite::user();
            
        } catch (InvalidStateException $e) {
            Log::warning("invalid state for oAuth login: ".get_class($e)." ".$e->getMessage());
            return redirect('/account/logincheck');
        }

        Log::debug("\$oauth_user=".json_encode($oauth_user, 192));


        $tokenly_uuid = $oauth_user->id;
        $oauth_token = $oauth_user->token;
        $username = $oauth_user->user['username'];
        $name = $oauth_user->user['name'];
        $email = $oauth_user->user['email'];

        // find an existing user based on the credentials provided
        $existing_user = $user_repository->findByTokenlyUuid($tokenly_uuid);
        if ($existing_user) {
            Auth::login($existing_user);

            // update if needed
            if (
                $existing_user['username'] != $username
                OR $existing_user['name'] != $name
                OR $existing_user['email'] != $email
            ) {
                $user_repository->update($existing_user, [
                    'username' => $username,
                    'name'     => $name,
                    'email'    => $email,
                ]);
            }

            $user_to_login = $existing_user;
        } else {
            // no user was found - create a new user based on the credentials we received
            $new_user = $user_repository->create([
                'username'     => $username,
                'name'         => $name,
                'email'        => $email,
                'tokenly_uuid' => $tokenly_uuid,
            ]);
            $user_to_login = $new_user;
        }

        Auth::login($user_to_login);

        return redirect('/account/login');

    }
}
