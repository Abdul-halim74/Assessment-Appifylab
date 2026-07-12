<?php

use App\Http\Middleware\JwtFromCookie;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', JwtFromCookie::class);

        // Laravel's default middleware priority list runs auth middleware
        // before any appended group middleware, regardless of group order —
        // without this, JwtFromCookie would inject the Authorization header
        // too late. The priority list keys on the *interface*, not the
        // concrete Authenticate class, so anchor to that.
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: JwtFromCookie::class,
        );

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Covers TokenExpiredException, TokenInvalidException, TokenBlacklistedException, etc.
        $exceptions->render(fn (JWTException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => 'Unauthenticated.'], 401)
            : null
        );
    })->create();
