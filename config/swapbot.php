<?php

return [

    'webhook_url' => env('XCHAIN_LOCAL_WEBHOOK_HOST', 'http://localhost').'/_xchain_client_receive',

    'site_host'   => env('SITE_HOST', 'http://swapbot.tokenly.co'),

    'defaultFee'  => 0.0001,
];

