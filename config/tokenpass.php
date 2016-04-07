<?php

return [
    'client_id'     => env('TOKENPASS_CLIENT_ID'),
    'client_secret' => env('TOKENPASS_CLIENT_SECRET'),

    // this is the URL that Tokenpass uses to redirect the user back to your application
    'redirect'      => env('SITE_HOST', 'https://swapbot.tokenly.com').'/account/authorize/callback',

    // this is the Tokenpass URL
    'base_url'      => rtrim(env('TOKENPASS_PROVIDER_HOST', 'https://tokenpass.tokenly.com'), '/'),
];
