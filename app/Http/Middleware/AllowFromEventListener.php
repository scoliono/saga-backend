<?php

namespace App\Http\Middleware;

use Closure;

class AllowFromEventListener
{
    private $whitelist = [];

    public function __construct()
    {
        $this->whitelist[] = config('app.event_listener_ip');
        if (config('app.env') === 'local') {
            $this->whitelist[] = '127.0.0.1';
            $this->whitelist[] = '::1';
        }
    }

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
        if (in_array($request->ip(), $this->whitelist)) {
            return $next($request);
        }
        dd($request->ip());
        return abort(403);
    }
}
