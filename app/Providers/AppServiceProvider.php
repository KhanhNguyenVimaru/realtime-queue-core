<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::enablePasswordGrant();
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        RateLimiter::for('login', function (Request $request): array {
            $email = strtolower((string) $request->input('email', ''));
            $ip = (string) $request->ip();
            $identity = $email !== '' ? $email.'|'.$ip : $ip;

            $response = function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Too many login attempts. Please try again later.',
                ], 429)->withHeaders($headers);
            };

            return [
                Limit::perMinute(5)->by($identity)->response($response),
                Limit::perMinute(20)->by($ip)->response($response),
            ];
        });
    }
}
