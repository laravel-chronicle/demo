<?php

use App\Http\Middleware\ResolveDemoPersona;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Fly.io terminates TLS at its edge and proxies plain HTTP to the
        // container; trust its forwarded headers so Laravel detects HTTPS and
        // generates https:// asset URLs (otherwise the browser blocks them as
        // mixed content). The machine is only reachable through Fly's proxy.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            ResolveDemoPersona::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
