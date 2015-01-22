<?php namespace Swapbot\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

class TestingSafeVerifyCsrfToken extends VerifyCsrfToken {


	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 *
	 * @throws \Illuminate\Session\TokenMismatchException
	 */
	public function handle($request, Closure $next)
	{
		// for testing, do not using this middleware
		if (app()->environment() == 'testing') {
			return $next($request);
		}

		// allow CSRF handling
		return parent::handle($request, $next);

	}

}
