<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AuthService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.auth.url'), '/');
    }

    public function register(array $data): Response
    {
        return $this->client()->post($this->url('/api/register'), $data);
    }

    public function login(array $data): Response
    {
        return $this->client()->post($this->url('/api/login'), $data);
    }

    public function logout(string $token): Response
    {
        return $this->client($token)->post($this->url('/api/auth/logout'));
    }

    public function me(string $token): Response
    {
        return $this->client($token)->get($this->url('/api/auth/me'));
    }

    public function updateProfile(string $token, array $data): Response
    {
        return $this->client($token)->put($this->url('/api/auth/profile'), $data);
    }

    public function updatePassword(string $token, array $data): Response
    {
        return $this->client($token)->put($this->url('/api/auth/password'), $data);
    }

    public function userFromToken(string $token): ?array
    {
        try {
            $response = $this->me($token);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $user = $response->json();

        return is_array($user) && isset($user['id']) ? $user : null;
    }

    private function client(?string $token = null): PendingRequest
    {
        $request = Http::timeout(10)->acceptJson()->asJson();

        return $token ? $request->withToken($token) : $request;
    }

    private function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }
}
