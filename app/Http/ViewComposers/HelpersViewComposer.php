<?php

namespace Swapbot\Http\ViewComposers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Formatting\FormattingHelper;
use Swapbot\Swap\Settings\Facade\Settings;

class HelpersViewComposer
{

    function __construct(FormattingHelper $formatting_helper) {
        $this->formatting_helper = $formatting_helper;
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // add config to all views
        // Settings
        $pusher_url = Config::get('tokenlyPusher.clientUrl');
        $view->with([
            'env'                      => app()->environment(),
            'pusherUrl'                => $pusher_url,

            'fmt'                      => $this->formatting_helper,
            'currency'                 => function($value, $places=null) { return $this->formatting_helper->formatCurrency($value, $places); },

            'tokenlyAccountsSiteUrl'   => rtrim(env('TOKENLY_ACCOUNTS_PROVIDER_HOST'), '/').'/',
            'tokenlyAccountsUpdateUrl' => rtrim(env('TOKENLY_ACCOUNTS_PROVIDER_HOST'), '/').'/auth/update',

            'quotebotPusherUrl'        => rtrim(env('QUOTEBOT_PUSHER_CLIENT_URL', $pusher_url), '/'),
            'analyticsId'              => env('GOOGLE_ANALYTICS_ID'),
            'quotebot'          => [
                'url'           => rtrim(Config::get('quotebot.connection_url'), '/'),
                'apiToken'      => Config::get('quotebot.api_token'),
            ],
        ]);
    }

}
