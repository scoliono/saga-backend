<?php

namespace App\Http\Middleware;

use Closure;

class AllowFromEventListener
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
        // decide with the team later whether to do IP based validation
        // or require some sort of key instead
        if ($request->ip() === config('app.event_listener_ip')) {
            return $next($request);
        }
        return abort(403);
    }
}
