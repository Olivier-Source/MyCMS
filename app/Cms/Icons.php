<?php

namespace App\Cms;

/**
 * Material Symbols icons (outlined) as SVG files, in resources/icons.
 * No icon font to download: each icon is inlined in the page.
 */
class Icons
{
    private static array $cache = [];

    public static function path(string $name): ?string
    {
        if (! preg_match('/^[a-z0-9_]{1,40}$/', $name)) {
            return null;
        }
        $file = resource_path('icons/'.$name.'.svg');

        return is_file($file) ? $file : null;
    }

    public static function exists(string $name): bool
    {
        return self::path($name) !== null;
    }

    /** SVG markup ready to insert (empty when the icon does not exist). */
    public static function svg(?string $name, string $class = ''): string
    {
        if (! $name || ! ($file = self::path($name))) {
            return '';
        }

        $svg = self::$cache[$name] ??= trim(file_get_contents($file));

        return preg_replace(
            '/^<svg /',
            '<svg class="icon '.e($class).'" fill="currentColor" aria-hidden="true" focusable="false" ',
            $svg,
            1
        );
    }

    /** @return string[] Available names (icon picker of the administration) */
    public static function all(): array
    {
        return collect(glob(resource_path('icons/*.svg')))
            ->map(fn ($f) => basename($f, '.svg'))
            ->sort()->values()->all();
    }
}
