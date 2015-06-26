<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

$router->get('/', 'WelcomeController@index');


////////////////////////////////////////////////////////////////////////
// Website

// admin
$router->get('/admin/{param1?}/{param2?}/{param3?}/{param4?}/{param5?}', 'AdminController@index');


////////////////////////////////////////////////////////////////////////
// Public Bot Pages
$router->get('/public/{username}/{botid}', 'PublicBotController@showBot');
$router->get('/public/{username}/swap/{swapid}', 'PublicSwapController@showSwap');

////////////////////////////////////////////////////////////////////////
// Unsubscribe Pages
$router->get('/public/unsubscribe/{customerid}/{token}', 'PublicEmailSubscriptionController@unsubscribe');


////////////////////////////////////////////////////////////////////////
// Public API

// Bot API
$router->get('api/v1/public/bot/{id}', 'API\Bot\PublicBotController@show');

// Bot Events API
$router->get('api/v1/public/botevents/{botuuid}', 'API\BotEvents\PublicBotEventsController@index');

// Swap Event Strem API
$router->get('api/v1/public/boteventstream/{botuuid}', 'API\BotEvents\PublicBotEventsController@botEventStreamIndex');

// Swap Event Stream API
$router->get('api/v1/public/swapevents/{botuuid}', 'API\BotEvents\PublicBotEventsController@swapsEventStreamIndex');

// Swaps API
$router->get('api/v1/public/swaps/{botuuid}', 'API\Swap\PublicSwapController@index');

// Customer API
$router->post('api/v1/public/customers', 'API\Customer\PublicCustomerController@store');

// Version API
$router->get('api/v1/public/version', 'API\Version\PublicVersionController@getVersion');





////////////////////////////////////////////////////////////////////////
// Protected API

// Bot API
$router->resource('api/v1/bots', 'API\Bot\BotController', ['except' => ['create','edit']]);

// Bot Plans API
$router->get('api/v1/plans', 'API\Bot\PlansController@getPaymentPlans');

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


// webhook notifications
Route::post('/_xchain_client_receive', 'WebhookController@receive');
