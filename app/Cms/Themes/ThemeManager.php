<?php

namespace App\Cms\Themes;

use App\Cms\Packages\PackageException;
use App\Cms\Packages\PackageFetcher;
use App\Cms\SiteSettings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * Discovers, activates, installs and removes themes.
 *
 * Public templates are loaded through the "theme::" view namespace, looked up
 * in the active theme, then its parent theme, then the bundled default theme:
 * a theme only needs to contain the templates it changes.
 */
class ThemeManager
{
    public const MANIFEST = 'theme.json';

    /** Files copied from a theme package */
    public const EXTENSIONS = ['blade.php', 'json', 'css', 'js', 'map', 'woff', 'woff2', 'ttf', 'otf', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'ico', 'avif', 'md', 'txt'];

    /** Files a theme may serve publicly (theme assets route) */
    public const PUBLIC_EXTENSIONS = ['css', 'js', 'woff', 'woff2', 'ttf', 'otf', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'ico', 'avif'];

    /** @var array<string, Theme>|null */
    private ?array $themes = null;

    public function __construct(private SiteSettings $settings, private PackageFetcher $fetcher) {}

    public static function bundledPath(): string
    {
        return resource_path('themes');
    }

    public static function installedPath(): string
    {
        return storage_path('app/themes');
    }

    /** @return array<string, Theme> */
    public function all(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }

        $themes = [];
        foreach ([self::bundledPath() => true, self::installedPath() => false] as $root => $bundled) {
            foreach (glob($root.'/*/'.self::MANIFEST) ?: [] as $manifest) {
                $dir = dirname($manifest);
                $slug = basename($dir);
                $data = json_decode((string) file_get_contents($manifest), true);
                if (! self::validSlug($slug) || ! is_array($data) || isset($themes[$slug])) {
                    continue;
                }
                $themes[$slug] = new Theme($slug, str_replace('\\', '/', $dir), $data, $bundled);
            }
        }
        ksort($themes);

        return $this->themes = $themes;
    }

    public function find(?string $slug): ?Theme
    {
        return $slug ? ($this->all()[$slug] ?? null) : null;
    }

    public function active(): Theme
    {
        return $this->find($this->settings->get('theme'))
            ?? $this->find(config('mycms.default_theme'))
            ?? throw new \RuntimeException('The default theme is missing (resources/themes/default).');
    }

    /** @return Theme[] Active theme, its parents, then the default theme */
    public function chain(): array
    {
        $chain = [];
        $theme = $this->active();
        while ($theme && count($chain) < 4 && ! isset($chain[$theme->slug])) {
            $chain[$theme->slug] = $theme;
            $theme = $this->find($theme->parent());
        }
        $default = $this->find(config('mycms.default_theme'));
        if ($default) {
            $chain[$default->slug] ??= $default;
        }

        return array_values($chain);
    }

    /** Registers the "theme::" view namespace. Called once per request. */
    public function registerViews(): void
    {
        $paths = array_filter(array_map(fn (Theme $t) => $t->viewsPath(), $this->chain()), 'is_dir');
        View::replaceNamespace('theme', array_values($paths));
    }

    /** Value of an option of the active theme (see "options" in theme.json). */
    public function option(string $name, mixed $default = null): mixed
    {
        $theme = $this->active();
        $values = $this->settings->get('theme_options.'.$theme->slug, []);
        if (is_array($values) && array_key_exists($name, $values)) {
            return $values[$name];
        }
        foreach ($theme->options() as $option) {
            if ($option['name'] === $name) {
                return $option['default'] ?? $default;
            }
        }

        return $default;
    }

    public function activate(string $slug): void
    {
        $theme = $this->find($slug) ?? throw new PackageException(__('This theme does not exist.'));
        $values = ['theme' => $theme->slug];

        // First activation: the theme's default colours are applied
        $palette = $theme->palettes()[$theme->defaultPalette()] ?? null;
        if ($palette && ! $this->settings->get('theme_palette_applied.'.$theme->slug)) {
            $values['palette'] = $palette['colors'];
            $values['theme_palette_applied'] = [$theme->slug => true] + (array) $this->settings->get('theme_palette_applied', []);
        }
        $this->settings->set($values);
        $this->themes = null;
        View::flushFinderCache();
        $this->registerViews();
    }

    /**
     * Installs (or updates) a theme from a Git repository / .zip address or an uploaded archive.
     *
     * @param  array{url?: string, zip?: string}  $source
     */
    public function install(array $source): Theme
    {
        $tmp = isset($source['url']) ? $this->fetcher->fromUrl($source['url']) : $this->fetcher->fromZip($source['zip']);
        $staging = null;

        try {
            $root = $this->fetcher->locateRoot($tmp, self::MANIFEST);
            $manifest = json_decode((string) file_get_contents($root.'/'.self::MANIFEST), true);
            if (! is_array($manifest) || empty($manifest['name']) || ! is_string($manifest['name'])) {
                throw new PackageException(__('The theme.json file is invalid: a "name" is required.'));
            }

            $slug = (string) ($manifest['slug'] ?? '');
            if (! self::validSlug($slug)) {
                throw new PackageException(__('The theme.json file is invalid: "slug" must contain only lowercase letters, digits and dashes.'));
            }
            $existing = $this->find($slug);
            if ($existing?->bundled) {
                throw new PackageException(__('A built-in theme already uses the name ":slug".', ['slug' => $slug]));
            }
            if (! is_dir($root.'/views')) {
                throw new PackageException(__('The theme has no "views" folder.'));
            }

            $staging = $this->fetcher->tempDir();
            $this->fetcher->copyAllowed($root, $staging, self::EXTENSIONS);
            File::put($staging.'/.source.json', json_encode([
                'url' => $source['url'] ?? null,
                'installed_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $target = self::installedPath().'/'.$slug;
            File::ensureDirectoryExists(self::installedPath());
            File::deleteDirectory($target);
            if (! File::moveDirectory($staging, $target)) {
                throw new PackageException(__('The theme could not be copied into storage/app/themes (check permissions).'));
            }
            $staging = null;
        } finally {
            $this->fetcher->cleanup($tmp, $staging);
        }

        $this->themes = null;
        View::flushFinderCache();

        return $this->find($slug);
    }

    /** Source address of an installed theme (for updates), if known. */
    public function sourceUrl(Theme $theme): ?string
    {
        $info = json_decode((string) @file_get_contents($theme->path.'/.source.json'), true);

        return is_array($info) && is_string($info['url'] ?? null) ? $info['url'] : null;
    }

    public function delete(string $slug): void
    {
        $theme = $this->find($slug) ?? throw new PackageException(__('This theme does not exist.'));
        if ($theme->bundled) {
            throw new PackageException(__('Built-in themes cannot be deleted.'));
        }
        if ($this->active()->slug === $slug) {
            throw new PackageException(__('Activate another theme before deleting this one.'));
        }
        foreach ($this->all() as $other) {
            if ($other->parent() === $slug) {
                throw new PackageException(__('The theme ":name" is based on this one.', ['name' => $other->name()]));
            }
        }

        File::deleteDirectory($theme->path);
        $this->themes = null;
    }

    public static function validSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9-]{0,49}$/', $slug);
    }
}
