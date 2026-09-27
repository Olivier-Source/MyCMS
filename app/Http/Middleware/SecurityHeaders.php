<?php

namespace App\Http\Middleware;

use App\Cms\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers and strict CSP policy (one nonce per request).
 * The public site runs no third-party script; fonts and icons are hosted
 * locally. Only OpenStreetMap maps and YouTube / Vimeo videos can be embedded.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(32);
        Vite::useCspNonce($nonce);
        view()->share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $isAdmin = $request->route()?->named('admin.*') ?? false;
        $headers = $response->headers;

        $headers->set('Content-Security-Policy', $this->policy($nonce, $isAdmin));
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($isAdmin) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $headers->set('Cache-Control', 'no-store, private');
        } elseif (! app(SiteSettings::class)->get('allow_indexing')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    private function policy(string $nonce, bool $admin): string
    {
        $self = "'self'";
        $hot = Vite::isRunningHot() ? ' '.$this->hotOrigins() : '';
        $site = $this->siteOrigin();

        $directives = [
            'default-src' => $self,
            'base-uri' => $self,
            'object-src' => "'none'",
            'form-action' => $self.($admin && $site ? ' '.$site : ''),
            // Media and theme files are served by the public domain
            'img-src' => "$self data: blob: $site",
            'font-src' => "$self $site".$hot,
            'connect-src' => $self.$hot.($hot ? ' ws: wss:' : ''),
            'frame-ancestors' => $self,
            'frame-src' => $admin ? "$self $site" : 'https://www.openstreetmap.org https://www.youtube-nocookie.com https://player.vimeo.com',
            // The administration uses Alpine.js, which needs 'unsafe-eval'.
            // The public site only runs scripts signed by the nonce or served by the site.
            'script-src' => "$self $site 'nonce-{$nonce}'".($admin ? " 'unsafe-eval'" : '').$hot,
            'style-src' => $admin ? "$self $site 'unsafe-inline'".$hot : "$self $site 'nonce-{$nonce}'".$hot,
        ];

        return collect($directives)->map(fn ($v, $k) => trim("$k $v"))->implode('; ');
    }

    private function siteOrigin(): string
    {
        $url = parse_url((string) config('app.url'));

        return isset($url['scheme'], $url['host'])
            ? $url['scheme'].'://'.$url['host'].(isset($url['port']) ? ':'.$url['port'] : '')
            : '';
    }

    private function hotOrigins(): string
    {
        $url = trim((string) @file_get_contents(public_path('hot')));

        return $url ? $url.' '.preg_replace('#^http#', 'ws', $url) : '';
    }
}
