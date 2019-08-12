<?php

namespace App\Http\Middleware;

use Closure;
use App\Resources\OauthResources;
use App\Resources\ResponseResources;

class Oauthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!OauthResources::check($request->get('access_token'))) {
            return ResponseResources::error(1000);
        }

        return $next($request);
    }
}
