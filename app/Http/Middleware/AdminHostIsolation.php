<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * On the administration sub-domain, only the administration routes answer:
 * the public site is not reachable there (and the administration routes are
 * bound to their domain in routes/admin.php).
 */
class AdminHostIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $domain = config('mycms.domain');

        if ($domain && strcasecmp($request->getHost(), $domain) === 0 && ! $request->routeIs('admin.*')) {
            abort(404);
        }

        return $next($request);
    }
}
