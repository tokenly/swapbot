<?php

return [
    'client_id'     => env('TOKENLY_ACCOUNTS_CLIENT_ID'),
    'client_secret' => env('TOKENLY_ACCOUNTS_CLIENT_SECRET'),

    // this is the URL that Tokenly Accounts uses to redirect the user back to your application
    'redirect'      => env('SITE_HOST', 'https://swapbot.tokenly.com').'/account/authorize/callback',

    // this is the Tokenly Accounts URL
    'base_url'      => rtrim(env('TOKENLY_ACCOUNTS_PROVIDER_HOST', 'https://accounts.tokenly.com'), '/'),
];
