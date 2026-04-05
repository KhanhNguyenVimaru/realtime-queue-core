<?php

namespace Tests\Unit;

use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    public function test_it_returns_401_when_user_is_not_authenticated(): void
    {
        $middleware = new AdminMiddleware;
        $request = Request::create('/api/admin', 'GET');

        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_it_returns_403_when_user_is_not_admin(): void
    {
        $middleware = new AdminMiddleware;
        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn () => new User(['role' => 'user']));

        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_it_allows_admin_user(): void
    {
        $middleware = new AdminMiddleware;
        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn () => new User(['role' => 'admin']));

        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
