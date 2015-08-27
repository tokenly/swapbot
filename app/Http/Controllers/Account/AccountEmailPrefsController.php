<?php

namespace Swapbot\Http\Controllers\Account;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Models\User;
use Swapbot\Repositories\UserRepository;
use Tokenly\LaravelEventLog\Facade\EventLog;

class AccountEmailPrefsController extends Controller
{


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function getEmailNotifications(Request $request, UserRepository $user_repository)
    {
        $logged_in_user = Auth::user();

        // $flashable = [];
        $flashable = ['prefs' => Session::hasOldInput('prefs') ? Session::getOldInput('prefs') : $logged_in_user['email_preferences']];
        $request->getSession()->flashInput($flashable);

        return view('public.account.emailprefs', ['user' => $logged_in_user, ]);
    }

    public function postEmailNotifications(Request $request, UserRepository $user_repository)
    {
        try {
            
            $logged_in_user = Auth::user();

            $attributes = $request->all();
            Log::debug("\$attributes=".json_encode($attributes, 192));

            $is_updated = false;
            $existing_prefs = $logged_in_user['email_preferences'];
            $new_email_preferences = $existing_prefs;
            foreach($existing_prefs as $name => $is_subscribed) {
                $new_subscribed_value = (isset($attributes[$name]) ? !!$attributes[$name] : false);
                if ($new_subscribed_value != $is_subscribed) {
                    $new_email_preferences[$name] = $new_subscribed_value;
                    $is_updated = true;
                }
            }


            Log::debug("\$is_updated=".json_encode($is_updated, 192));
            Log::debug("\$new_email_preferences=".json_encode($new_email_preferences, 192));
            if ($is_updated) {
                $user_repository->update($logged_in_user, ['email_preferences' => $new_email_preferences]);
            }

            return view('public.account.emailprefs-complete', ['user' => $logged_in_user, ]);

        } catch (Exception $e) {
            EventLog::logError('updateEmailNotifications.failed', $e);
            return redirect()->back()->withInput(['prefs' => $new_email_preferences])->withErrors(['generic' => "Failed to update your preferences."]);
        }


    }

}
