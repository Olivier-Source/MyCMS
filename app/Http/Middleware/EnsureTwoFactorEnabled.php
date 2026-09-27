<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When two-factor authentication is required (ADMIN_REQUIRE_2FA, on by
 * default), only its set-up page is reachable until it is enabled.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('mycms.require_2fa') && ! $request->user()->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.setup');
        }

        return $next($request);
    }
}
