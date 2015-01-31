<?php namespace Swapbot\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Http\Requests;

class AdminController extends Controller {

	/**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */
    public function index()
    {
        // if (app()->environment() == 'local') {
        //     // read development scripts in order?
        // } else {
        //     $admin_scripts = ['admin-combined.js'];
        // }

        $admin_scripts = ['admin-combined.js'];

        return view('admin.index', [
            'pusherUrl'     => Config::get('tokenlyPusher.serverUrl'),
            'admin_scripts' => $admin_scripts,
        ]);
    }

}
