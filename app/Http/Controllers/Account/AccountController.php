<?php

namespace Swapbot\Http\Controllers\Account;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Models\User;
use Swapbot\Repositories\UserRepository;
use Tokenly\LaravelEventLog\Facade\EventLog;

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

        try {
            
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

        $oauth_user = Socialite::user();
           

        Log::debug("\$oauth_user=".json_encode($oauth_user, 192));


        $tokenly_uuid = $oauth_user->id;
        $oauth_token = $oauth_user->token;
        $username = $oauth_user->user['username'];
        $name = $oauth_user->user['name'];
        $email = $oauth_user->user['email'];
        $email_is_confirmed = $oauth_user->user['email_is_confirmed'];

        // find an existing user based on the credentials provided
        $existing_user = $user_repository->findByTokenlyUuid($tokenly_uuid);
        if ($existing_user) {
            Auth::login($existing_user);

            // update if needed
            if (
                $existing_user['username'] != $username
                OR $existing_user['name'] != $name
                OR $existing_user['email'] != $email
                OR $existing_user['email_is_confirmed'] != $email_is_confirmed
                OR $existing_user['oauth_token'] != $oauth_token
            ) {
                $user_repository->update($existing_user, [
                    'username'           => $username,
                    'name'               => $name,
                    'email'              => $email,
                    'email_is_confirmed' => $email_is_confirmed,
                    'oauth_token'        => $oauth_token,
                ]);
            }

            $user_to_login = $existing_user;
        } else {
            // no user was found - create a new user based on the credentials we received
            $new_user = $user_repository->create([
                'username'           => $username,
                'name'               => $name,
                'email'              => $email,
                'email_is_confirmed' => $email_is_confirmed,
                'tokenly_uuid'       => $tokenly_uuid,
                'oauth_token'        => $oauth_token,
            ]);
            $user_to_login = $new_user;
        }

        Auth::login($user_to_login);

        return redirect('/account/login');

        } catch (Exception $e) {
            EventLog::logError('oauth.callback.failed', $e, ['exceptionClass' => get_class($e)]);

            return view('public.oauth.authorization-failed', ['error_msg' => 'Failed to authenticate this user.']);
        }
    }



    /**
     * Obtain the user information from GitHub.
     *
     * @return Response
     */
    public function sync(Request $request, UserRepository $user_repository)
    {

        try {
            $logged_in_user = Auth::user();

            $oauth_user = null;
            try {
                if ($logged_in_user['oauth_token']) {
                    $oauth_user = Socialite::getUserByExistingToken($logged_in_user['oauth_token']);
                    Log::debug("\$oauth_user=".json_encode($oauth_user, 192));
                }
            } catch (Exception $e) {
                // failed to sync
            }

            if ($oauth_user) {

                $tokenly_uuid = $oauth_user->id;
                $oauth_token = $oauth_user->token;
                $username = $oauth_user->user['username'];
                $name = $oauth_user->user['name'];
                $email = $oauth_user->user['email'];
                $email_is_confirmed = $oauth_user->user['email_is_confirmed'];

                // find an existing user based on the credentials provided
                $existing_user = $user_repository->findByTokenlyUuid($tokenly_uuid);
                if ($existing_user) {
                    // update if needed
                    if (
                        $existing_user['username'] != $username
                        OR $existing_user['name'] != $name
                        OR $existing_user['email'] != $email
                        OR $existing_user['email_is_confirmed'] != $email_is_confirmed
                    ) {
                        $user_repository->update($existing_user, [
                            'username'           => $username,
                            'name'               => $name,
                            'email'              => $email,
                            'email_is_confirmed' => $email_is_confirmed,
                        ]);
                    }
                }

                $logged_in_user = $existing_user;

                $synced = true;
            } else {
                $synced = false;
            }


            return view('public.account.sync', ['synced' => $synced, 'user' => $logged_in_user, ]);


        } catch (Exception $e) {
            EventLog::logError('oauth.sync.failed', $e, ['exceptionClass' => get_class($e)]);

            return view('public.oauth.sync-failed', ['error_msg' => 'Failed to sync this user.']);
        }
    }

}
