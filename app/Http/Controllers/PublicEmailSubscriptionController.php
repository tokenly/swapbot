<?php namespace Swapbot\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Swapbot\Commands\UnsubscribeCustomer;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Http\Requests;
use Swapbot\Models\Formatting\FormattingHelper;
use Swapbot\Repositories\CustomerRepository;
use Tokenly\LaravelEventLog\Facade\EventLog;

class PublicEmailSubscriptionController extends Controller {

    use DispatchesCommands;

    function __construct(CustomerRepository $customer_repository) {
        $this->customer_repository = $customer_repository;
    }

    /**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */
    public function unsubscribe($customerid, $token)
    {
        $customer = $this->customer_repository->findByUuid($customerid);

        $error = null;
        try {
            if ($customer) {
                $swap = $customer->swap;
                $bot = $swap->bot;
            } else {
                $error = "This email subscription was not found.";
            }

            if ($error === null) {
                if ($customer['unsubscribe_token'] == $token) {
                    // do the unsubscribe
                    $this->dispatch(new UnsubscribeCustomer($customer));
                } else {
                    $error = "This email subscription token was not found.";
                }
            }

            if ($error === null) {
                return view('public.unsubscribe.success', [
                    'customer'      => $customer,
                    'swap'          => $swap,
                    'bot'           => $bot,
                    'env'           => app()->environment(),
                ]);
            }
            
            // a user error happened
            EventLog::logError('unsubscribe.user.error', $error);
        } catch (Exception $e) {
            EventLog::logError('unsubscribe.error', $e->getMessage());
            $error = "An unexpected error occurred.";
        }

        return view('public.unsubscribe.error', [
            'error'          => $error,
        ]);

    }


}
