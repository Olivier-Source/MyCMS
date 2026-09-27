<?php

use App\Http\Middleware\AdminHostIsolation;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
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
        // Behind a reverse proxy (Traefik, Nginx, Caddy…): HTTPS and real visitor IP
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));

        // First in the chain: security headers also apply to error pages
        $middleware->web(prepend: [SecurityHeaders::class]);
        $middleware->web(append: [AdminHostIsolation::class]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('mycms:purge')->dailyAt('03:30');
        $schedule->command('auth:clear-resets')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
