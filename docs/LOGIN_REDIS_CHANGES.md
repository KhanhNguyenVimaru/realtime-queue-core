# Login + Redis Changes

- Added rate limiting with lockout responses for login using `RateLimiter::for('login')`.
- Applied `throttle:login` middleware to the login route.
- Switched session storage to Redis in `docker/.env.backend`.

## Files Updated

- app/Providers/AppServiceProvider.php
- routes/api.php
- docker/.env.backend
