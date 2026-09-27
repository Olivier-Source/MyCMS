<?php

use App\Cms\Themes\ThemeManager;

if (! function_exists('site_url')) {
    /**
     * Absolute URL on the public domain of the site (APP_URL), including when
     * it is generated from the administration sub-domain.
     */
    function site_url(string $path = '/'): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}

if (! function_exists('theme_option')) {
    /**
     * Value of an option of the active theme (see "options" in theme.json).
     */
    function theme_option(string $name, mixed $default = null): mixed
    {
        return app(ThemeManager::class)->option($name, $default);
    }
}
