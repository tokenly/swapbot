<?php

use Illuminate\Routing\Router;


////////////////////////////////////////////////////////////////////////
// Website Routes

// home
$router->get('/', 'WelcomeController@index');

// admin
$router->get('/admin/{param1?}/{param2?}/{param3?}/{param4?}/{param5?}', 'AdminController@index');

// Public Bot Pages
$router->get('/bot/{username}/{botid}', 'PublicBotController@showBot');
$router->get('/swap/{username}/{swapid}', 'PublicSwapController@showSwap');

// redirect old pages
$router->get('/public/{username}/{botid}', 'PublicBotController@redirectToCanonicalBotURL');
$router->get('/public/{username}/swap/{swapid}', 'PublicSwapController@redirectToCanonicalSwapURL');


// Unsubscribe Pages
$router->get('/public/unsubscribe/{customerid}/{token}', 'PublicEmailSubscriptionController@unsubscribe');


////////////////////////////////////////////////////////////////////////
// Account Routes

$router->get('/account/login', 'Account\AccountController@login');
$router->get('/account/credentialscheck', 'Account\AccountController@credentialsCheck');
$router->get('/account/welcome', 'Account\AccountController@welcome');
$router->get('/account/logout', 'Account\AccountController@logout');

$router->get('/account/authorize', 'Account\AccountController@redirectToProvider');
$router->get('/account/authorize/callback', 'Account\AccountController@handleProviderCallback');

$router->get('/account/sync', 'Account\AccountController@sync');

$router->get('/account/emails', 'Account\AccountEmailPrefsController@getEmailNotifications');
$router->post('/account/emails', 'Account\AccountEmailPrefsController@postEmailNotifications');


////////////////////////////////////////////////////////////////////////
// Public API

$router->group(['middleware' => 'cors'], function(Router $router) {

    // Bot API
    $router->get('api/v1/public/bot/{id}', 'API\Bot\PublicBotController@show');

    // Bots API
    $router->get('api/v1/public/bots', 'API\Bot\PublicBotController@showBots');

    // Bot Events API
    $router->get('api/v1/public/botevents/{botuuid}', 'API\BotEvents\PublicBotEventsController@index');

    // Bot Event Stream API
    $router->get('api/v1/public/boteventstream/{botuuid}', 'API\BotEvents\PublicBotEventsController@botEventStreamIndex');

    // Swap Event Stream API
    $router->get('api/v1/public/swapevents/{botuuid}', 'API\BotEvents\PublicBotEventsController@swapsEventStreamIndex');

    // Swaps API
    $router->get('api/v1/public/swaps/{botuuid}', 'API\Swap\PublicSwapController@index');

    // Available Swaps API
    $router->get('api/v1/public/availableswaps', 'API\Swap\PublicAvailableSwapsController@index');

    // Customer API
    $router->post('api/v1/public/customers', 'API\Customer\PublicCustomerController@store');

    // Version API
    $router->get('api/v1/public/version', 'API\Version\PublicVersionController@getVersion');

    // Global Alert API
    $router->resource('api/v1/globalalert', 'API\Settings\PublicGlobalAlertController@getGlobalAlert');

});




////////////////////////////////////////////////////////////////////////
// Protected API

// Bot API
$router->resource('api/v1/bots', 'API\Bot\BotController', ['except' => ['create','edit']]);

// shutdown bot API
$router->post('api/v1/bots/shutdown/{botuuid}', 'API\Bot\BotController@shutdown');

// Bot Plans API
$router->get('api/v1/plans', 'API\Bot\PlansController@getPaymentPlans');

// Priced Tokens API
$router->get('api/v1/pricedtokens', 'API\Bot\PricedTokensController@getPricedTokens');

// Balance Refresh
$router->post('api/v1/balancerefresh/{botuuid}', 'API\BalanceRefresh\BalanceRefreshController@refresh');

// Bot Events API
$router->get('api/v1/botevents/{botuuid}', 'API\BotEvents\BotEventsController@index', ['only' => ['index']]);

// User API
$router->resource('api/v1/users', 'API\User\UserController', ['except' => ['create','edit']]);

// Settings API
$router->resource('api/v1/settings', 'API\Settings\SettingsController', ['except' => ['create','edit']]);

// Payments API
$router->get('api/v1/payments/{botuuid}/all', 'API\Payments\PaymentsController@index');
$router->get('api/v1/payments/{botuuid}/balances', 'API\Payments\PaymentsController@balances');
// $router->get('api/v1/payments/{botuuid}/prices', 'API\Payments\PaymentsController@prices');

// Image API
$router->post('api/v1/images', 'API\Image\ImageController@store');
$router->put('api/v1/images', 'API\Image\ImageController@store');
$router->get('api/v1/images', 'API\Image\ImageController@show');

// Swaps API (admin)
$router->get('api/v1/swaps/{swapuuid}', 'API\Swap\SwapsController@show');
$router->get('api/v1/swaps', 'API\Swap\SwapsController@index');

// Whitelists API
$router->resource('api/v1/whitelists', 'API\Whitelist\WhitelistController', ['except' => ['create','edit']]);


// webhook notifications
$router->post('/_xchain_client_receive', 'WebhookController@receive');
