<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The JWT lives in an httpOnly cookie (never localStorage, to keep it out of
 * reach of XSS). jwt-auth's parser only looks at the Authorization header, so
 * this copies the cookie value into that header before the auth:api guard runs.
 */
class JwtFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->headers->has('Authorization') && $request->cookie('access_token')) {
            $request->headers->set('Authorization', 'Bearer '.$request->cookie('access_token'));
        }

        return $next($request);
    }
}
