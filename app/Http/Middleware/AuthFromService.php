<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Symfony\Component\HttpFoundation\Response;

class AuthFromService
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = app(AuthService::class)->userFromToken($token);

        if (! $user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $authUser = new Fluent($user);

        $request->merge(['auth_user' => $user]);
        $request->setUserResolver(fn () => $authUser);

        return $next($request);
    }
}
