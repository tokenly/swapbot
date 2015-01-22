<?php namespace Swapbot\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use Swapbot\Repositories\BotRepository;

class HomeController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Home Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders your application's "dashboard" for users that
	| are authenticated. Of course, you are free to change or remove the
	| controller as you wish. It is just here to get your app started!
	|
	*/

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware('auth');
	}

	/**
	 * Show the application dashboard to the user.
	 *
	 * @return Response
	 */
	public function index(BotRepository $repository, Guard $auth)
	{

		$user = $auth->getUser();
		$bots = $repository->findByUserID($user['id']);
		return view('home', ['bots' => $bots->get()]);
	}

}
