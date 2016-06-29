<?php

namespace Swapbot\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class RequireTLS
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (env('USE_SSL', false) && env('APP_ENV') != 'testing' && !$request->secure()) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request); 
    }
}
