<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'first_name' => $request->string('first_name'),
            'last_name' => $request->string('last_name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
        ]);

        $token = Auth::guard('api')->login($user);

        return response()->json(['user' => new UserResource($user)], 201)
            ->withCookie($this->tokenCookie($token));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $request->authenticate();

        return response()->json(['user' => new UserResource(Auth::guard('api')->user())])
            ->withCookie($this->tokenCookie($token));
    }

    public function refresh(): JsonResponse
    {
        $token = Auth::guard('api')->refresh();

        return response()->json(['message' => 'Token refreshed.'])
            ->withCookie($this->tokenCookie($token));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Logged out.'])
            ->withCookie($this->forgetTokenCookie());
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => new UserResource($request->user())]);
    }

    private function tokenCookie(string $token): Cookie
    {
        $ttlMinutes = Auth::guard('api')->factory()->getTTL();

        return cookie(
            name: 'access_token',
            value: $token,
            minutes: $ttlMinutes,
            path: '/',
            domain: null,
            secure: app()->isProduction(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );
    }

    private function forgetTokenCookie(): Cookie
    {
        return cookie()->forget('access_token');
    }
}
