<?php

namespace App\Http\Controllers\Site;

use App\Cms\Themes\ThemeManager;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the public files of a theme (styles, scripts, fonts, pictures).
 * Templates and manifests are never served.
 */
class ThemeAssetController extends Controller
{
    private const MIME = [
        'css' => 'text/css; charset=UTF-8', 'js' => 'text/javascript; charset=UTF-8',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp',
        'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'avif' => 'image/avif',
    ];

    public function __invoke(ThemeManager $themes, string $theme, string $path): BinaryFileResponse
    {
        $theme = $themes->find($theme) ?? abort(404);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, ThemeManager::PUBLIC_EXTENSIONS, true) && ! str_contains($path, '..'), 404);

        $root = realpath($theme->path);
        $file = realpath($theme->path.'/'.$path);
        abort_unless($root && $file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file), 404);

        return response()->file($file, [
            'Content-Type' => self::MIME[$extension],
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            // An SVG opened directly cannot run scripts
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; font-src 'self'",
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
