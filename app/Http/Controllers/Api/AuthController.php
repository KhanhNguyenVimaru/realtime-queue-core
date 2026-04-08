<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Jobs\LogUserLogin;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    private const REFRESH_COOKIE = 'realtime-queue-core';

    public function __construct(private AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = $this->authService->register($credentials);

        return $this->issueTokenPair($request, [
            'grant_type' => 'password',
            'username' => $credentials['email'],
            'password' => $credentials['password'],
            'scope' => '',
        ], $user, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = $this->authService->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Wrong password.'],
            ]);
        }

        LogUserLogin::dispatch(
            $user->id,
            $user->email,
            $request->ip(),
            $request->userAgent(),
        );

        return $this->issueTokenPair($request, [
            'grant_type' => 'password',
            'username' => $credentials['email'],
            'password' => $credentials['password'],
            'scope' => '',
        ], $user);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie(self::REFRESH_COOKIE);

        if (! $refreshToken) {
            return $this->unauthorizedResponse('Refresh token cookie is missing.');
        }

        $currentRefreshTokenId = $this->getJwtClaim($refreshToken, 'jti');
        $currentAccessTokenId = $currentRefreshTokenId
            ? DB::table('oauth_refresh_tokens')->where('id', $currentRefreshTokenId)->value('access_token_id')
            : null;

        $response = $this->requestPassportToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => '',
        ]);

        if (! $response['ok']) {
            return $this->unauthorizedResponse('Refresh token is invalid or expired.');
        }

        $this->revokeTokenBatch(
            accessTokenIds: $currentAccessTokenId ? [$currentAccessTokenId] : [],
            refreshTokenIds: $currentRefreshTokenId ? [$currentRefreshTokenId] : [],
        );

        $userId = $this->getJwtClaim($response['body']['access_token'], 'sub');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return $this->unauthorizedResponse('Authenticated user could not be resolved.');
        }

        return $this->tokenResponse($request, $response['body'], $user);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse('Authenticated user could not be resolved.');
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $currentAccessTokenId = optional($request->user()?->token())->id;
        $refreshToken = $request->cookie(self::REFRESH_COOKIE);
        $refreshTokenId = $refreshToken ? $this->getJwtClaim($refreshToken, 'jti') : null;
        $refreshAccessTokenId = $refreshTokenId
            ? DB::table('oauth_refresh_tokens')->where('id', $refreshTokenId)->value('access_token_id')
            : null;

        $accessTokenIds = array_filter([
            $currentAccessTokenId,
            $refreshAccessTokenId,
        ]);

        $refreshTokenIds = $refreshTokenId ? [$refreshTokenId] : [];

        $this->revokeTokenBatch(
            accessTokenIds: $accessTokenIds,
            refreshTokenIds: $refreshTokenIds,
        );

        return response()->json([
            'message' => 'Successfully logged out.',
        ])->withCookie($this->expiredRefreshCookie($request));
    }

    private function issueTokenPair(Request $request, array $payload, User $user, int $status = 200): JsonResponse
    {
        $response = $this->requestPassportToken($payload);

        if (! $response['ok']) {
            return response()->json([
                'message' => $response['body']['message'] ?? 'Unable to issue tokens.',
                'errors' => $response['body']['errors'] ?? null,
            ], $response['status']);
        }

        return $this->tokenResponse($request, $response['body'], $user, $status);
    }

    private function tokenResponse(Request $request, array $payload, User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $payload['access_token'],
            'token_type' => $payload['token_type'],
            'expires_at' => now()->addSeconds((int) $payload['expires_in'])->toIso8601String(),
            'user' => $user,
        ], $status)->withCookie($this->makeRefreshCookie($request, $payload['refresh_token']));
    }

    private function requestPassportToken(array $payload): array
    {
        $clientId = config('services.passport.password_client_id') ?? env('PASSPORT_PASSWORD_CLIENT_ID');
        $clientSecret = config('services.passport.password_client_secret') ?? env('PASSPORT_PASSWORD_CLIENT_SECRET');

        if (! $clientId || ! $clientSecret) {
            return [
                'ok' => false,
                'status' => 500,
                'body' => [
                    'message' => 'Passport password grant client is not configured.',
                    'errors' => [
                        'passport' => [
                            'Set PASSPORT_PASSWORD_CLIENT_ID and PASSPORT_PASSWORD_CLIENT_SECRET in .env.',
                        ],
                    ],
                ],
            ];
        }

        $internalRequest = Request::create('/oauth/token', 'POST', array_merge($payload, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]));

        $internalRequest->headers->set('Accept', 'application/json');

        $response = app()->handle($internalRequest);
        $body = json_decode($response->getContent(), true);

        return [
            'ok' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
            'status' => $response->getStatusCode(),
            'body' => is_array($body) ? $body : ['message' => 'Invalid token response.'],
        ];
    }

    private function revokeTokenBatch(array $accessTokenIds, array $refreshTokenIds): void
    {
        $accessTokenIds = array_values(array_unique(array_filter($accessTokenIds)));
        $refreshTokenIds = array_values(array_unique(array_filter($refreshTokenIds)));

        if ($accessTokenIds === [] && $refreshTokenIds === []) {
            return;
        }

        DB::transaction(function () use ($accessTokenIds, $refreshTokenIds): void {
            if ($accessTokenIds !== []) {
                DB::table('oauth_access_tokens')
                    ->whereIn('id', $accessTokenIds)
                    ->update(['revoked' => true]);
            }

            if ($refreshTokenIds !== [] || $accessTokenIds !== []) {
                DB::table('oauth_refresh_tokens')
                    ->where(function ($query) use ($refreshTokenIds, $accessTokenIds): void {
                        if ($refreshTokenIds !== []) {
                            $query->whereIn('id', $refreshTokenIds);
                        }

                        if ($accessTokenIds !== []) {
                            $query->orWhereIn('access_token_id', $accessTokenIds);
                        }
                    })
                    ->update(['revoked' => true]);
            }
        });
    }

    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }

    private function makeRefreshCookie(Request $request, string $refreshToken): Cookie
    {
        return cookie(
            self::REFRESH_COOKIE,
            $refreshToken,
            60 * 24 * 30,
            '/',
            config('session.domain'),
            $request->isSecure() || (bool) config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax'),
        );
    }

    private function expiredRefreshCookie(Request $request): Cookie
    {
        return cookie(
            self::REFRESH_COOKIE,
            '',
            -1,
            '/',
            config('session.domain'),
            $request->isSecure() || (bool) config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax'),
        );
    }

    private function getJwtClaim(string $jwt, string $claim): ?string
    {
        $segments = explode('.', $jwt);

        if (count($segments) < 2) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($segments[1]), true);

        if (! is_array($payload) || ! array_key_exists($claim, $payload)) {
            return null;
        }

        return (string) $payload[$claim];
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'));
    }
}
