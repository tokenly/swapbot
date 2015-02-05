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

Route::get('/', 'WelcomeController@index');
Route::get('/admin/{param1?}/{param2?}/{param3?}/{param4?}/{param5?}', 'AdminController@index');

// Route::get('home', 'HomeController@index');
// Route::controllers([
//     'auth'     => 'Auth\AuthController',
//     'password' => 'Auth\PasswordController',
//     'bot'      => 'Bot\BotController',
// ]);



// Bot API
$router->resource('api/v1/bots', 'API\Bot\BotController', ['except' => ['create','edit']]);

// Bot Events API
$router->get('api/v1/botevents/{botuuid}', 'API\BotEvents\BotEventsController@index', ['only' => ['index']]);

// User API
$router->resource('api/v1/users', 'API\User\UserController', ['except' => ['create','edit']]);
// $router->resource('api/v1/users', 'API\User\UserController', ['only' => ['show',]]);


// webhook notifications

Route::post('/_xchain_client_receive', 'WebhookController@receive');
