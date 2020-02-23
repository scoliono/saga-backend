<?php

namespace App\Http\Middleware;

use Closure;
use \Illuminate\Support\Facades\Cache;
use \Firebase\JWT\JWT;

class AllowFromEventListener
{
    private $whitelist = [];

    public function __construct()
    {
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
        if (in_array($request->ip(), $this->whitelist)) {
            return $next($request);
        }

        $jwt = \str_after($request->header('Authorization'), 'Bearer ');
        if (!$jwt) {
            return abort(403);
        }
        try {
            $decoded = JWT::decode($jwt, Cache::get('jwt_client_secret'), ['HS256']);
        } catch (\Exception $e) {
            return abort(403);
        }
        if ($decoded) {
            return $next($request);
        }
        return abort(403);
    }
}
