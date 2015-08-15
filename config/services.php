<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => '',
        'secret' => '',
    ],

    'mandrill' => [
        'secret' => env('MANDRILL_API_KEY'),
    ],

    'ses' => [
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
    ],

    'stripe' => [
        'model'  => 'User',
        'secret' => '',
    ],

    'tokenlyAccounts' => [
        'client_id'     => env('TOKENLY_ACCOUNTS_CLIENT_ID'),
        'client_secret' => env('TOKENLY_ACCOUNTS_CLIENT_SECRET'),
        'redirect'      => env('SITE_HOST', 'http://swapbot.tokenly.com').'/account/authorize/callback',
        'base_url'      => rtrim(env('TOKENLY_ACCOUNTS_PROVIDER_HOST', 'http://accounts.tokenly.com'), '/'),
    ],

];
