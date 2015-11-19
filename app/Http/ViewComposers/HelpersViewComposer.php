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
            'manifest'                 => function($filename) { return $this->manifest($filename); },

            'tokenlyAccountsSiteUrl'   => rtrim(env('TOKENLY_ACCOUNTS_PROVIDER_HOST'), '/').'/',
            'tokenlyAccountsUpdateUrl' => rtrim(env('TOKENLY_ACCOUNTS_PROVIDER_HOST'), '/').'/auth/update',

            'robohashUrl'              => rtrim(env('ROBOHASH_URL', 'https://robohash.tokenly.com'), '/'),
            'quotebotPusherUrl'        => rtrim(env('QUOTEBOT_PUSHER_CLIENT_URL', $pusher_url), '/'),
            'analyticsId'              => env('GOOGLE_ANALYTICS_ID'),
            'quotebot'          => [
                'url'           => rtrim(Config::get('quotebot.connection_url'), '/'),
                'apiToken'      => Config::get('quotebot.api_token'),
            ],

            'bugsnag'           => [
                'apiKey'        => env('BUGSNAG_API_KEY'),
                'releaseStage'  => env('BUGSNAG_RELEASE_STAGE', 'production'),
            ],


            'tawk'           => [
                'active'  => !!env('TAWK_ACTIVE'),
                'embedId' => env('TAWK_EMBED_ID'),
            ],


            'manifest'
        ]);
    }

    public function manifest($source_filename) {
        if (!isset($this->manifest_data)) {
            $this->manifest_data = [];
            $json_filepath = realpath(base_path('public/manifest/rev-manifest.json'));
            if ($json_filepath AND file_exists($json_filepath)) {
                $this->manifest_data = json_decode(file_get_contents($json_filepath), true);
            }
        }
        return isset($this->manifest_data[$source_filename]) ? $this->manifest_data[$source_filename] : $source_filename;
    }

}
