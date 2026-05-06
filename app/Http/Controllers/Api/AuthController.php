<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request, AuthService $authService): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        return $this->proxy(fn () => $authService->register($data));
    }

    public function login(Request $request, AuthService $authService): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        return $this->proxy(fn () => $authService->login($data));
    }

    public function logout(Request $request, AuthService $authService): JsonResponse
    {
        return $this->proxy(fn () => $authService->logout($request->bearerToken()));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request, AuthService $authService): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'sometimes|string|max:100',
        ]);

        return $this->proxy(fn () => $authService->updateProfile($request->bearerToken(), $data));
    }

    public function updatePassword(Request $request, AuthService $authService): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', Password::min(8)->mixedCase()->numbers()],
        ]);

        return $this->proxy(fn () => $authService->updatePassword($request->bearerToken(), $data));
    }

    private function proxy(Closure $request): JsonResponse
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            return response()->json(['message' => 'Auth service unavailable'], 503);
        }

        return response()->json($response->json(), $response->status());
    }
}
