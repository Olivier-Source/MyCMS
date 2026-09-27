<?php

namespace App\Http\Middleware;

use App\Cms\Languages\LanguageManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Language of the administration: the one chosen by the administrator in
 * "My account", otherwise the default language of the administration.
 */
class SetAdminLocale
{
    public function __construct(private LanguageManager $languages) {}

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->languages->adminLocale($request->user()));

        return $next($request);
    }
}
