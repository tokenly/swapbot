<?php

return [

    'webhook_url'                 => env('XCHAIN_LOCAL_WEBHOOK_HOST', 'http://localhost').'/_xchain_client_receive',

    'site_host'                   => env('SITE_HOST', 'http://swapbot.tokenly.co'),

    'defaultFee'                  => 0.0001,

    'robohash_url'                => env('ROBOHASH_HOST', 'http://robohash.org').'/%%HASH%%.png?set=set3',

    'xchain_fuel_pool_address'    => env('XCHAIN_FUEL_POOL_ADDRESS'),
    'xchain_fuel_pool_address_id' => env('XCHAIN_FUEL_POOL_ADDRESS_ID'),
];

