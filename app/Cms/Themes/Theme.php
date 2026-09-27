<?php

namespace App\Cms\Themes;

/**
 * A theme: a folder containing a theme.json manifest, Blade templates in
 * views/, pre-built assets (CSS, JS, fonts, images) and optional translations
 * in lang/{locale}.json.
 *
 * See docs/themes.md for the manifest format.
 */
final class Theme
{
    public function __construct(
        public readonly string $slug,
        public readonly string $path,
        public readonly array $manifest,
        public readonly bool $bundled,
    ) {}

    public function name(): string
    {
        return (string) ($this->manifest['name'] ?? $this->slug);
    }

    public function version(): string
    {
        return (string) ($this->manifest['version'] ?? '1.0.0');
    }

    public function description(): string
    {
        return (string) ($this->manifest['description'] ?? '');
    }

    public function author(): string
    {
        return (string) ($this->manifest['author'] ?? '');
    }

    public function homepage(): ?string
    {
        $url = $this->manifest['homepage'] ?? null;

        return is_string($url) && str_starts_with($url, 'https://') ? $url : null;
    }

    public function parent(): ?string
    {
        $parent = $this->manifest['parent'] ?? null;

        return is_string($parent) && $parent !== $this->slug ? $parent : null;
    }

    public function viewsPath(): string
    {
        return $this->path.'/views';
    }

    public function langFile(string $locale): string
    {
        return $this->path.'/lang/'.$locale.'.json';
    }

    public function screenshot(): ?string
    {
        $file = $this->manifest['screenshot'] ?? 'screenshot.png';

        return is_string($file) && is_file($this->path.'/'.$file) ? $this->assetUrl($file) : null;
    }

    /** Public address of a theme file (served by the /themes/{theme}/{path} route). */
    public function assetUrl(string $file): string
    {
        $file = ltrim($file, '/');
        $mtime = @filemtime($this->path.'/'.$file) ?: 0;

        return site_url('/themes/'.$this->slug.'/'.$file).'?v='.substr(md5($this->version().$mtime), 0, 10);
    }

    /** @return string[] Stylesheet URLs */
    public function styles(): array
    {
        return $this->assets('css');
    }

    /** @return string[] Script URLs */
    public function scripts(): array
    {
        return $this->assets('js');
    }

    /** @return array<string, array{name: string, colors: array<string, string>}> */
    public function palettes(): array
    {
        $palettes = [];
        foreach ((array) ($this->manifest['palettes'] ?? []) as $key => $palette) {
            if (is_string($key) && is_array($palette['colors'] ?? null)) {
                $palettes[$key] = ['name' => (string) ($palette['name'] ?? $key), 'colors' => $palette['colors']];
            }
        }

        return $palettes;
    }

    public function defaultPalette(): ?string
    {
        $key = $this->manifest['default_palette'] ?? null;

        return is_string($key) ? $key : null;
    }

    /** @return array<string, array> Blocks added by the theme (same format as core blocks) */
    public function blocks(): array
    {
        return array_filter((array) ($this->manifest['blocks'] ?? []), fn ($def, $type) => is_string($type)
            && preg_match('/^[a-z][a-z0-9_]{1,40}$/', $type) && is_array($def) && is_array($def['fields'] ?? null), ARRAY_FILTER_USE_BOTH);
    }

    /** @return array<int, array> Options shown in "Appearance" (name, label, type, default…) */
    public function options(): array
    {
        return array_values(array_filter((array) ($this->manifest['options'] ?? []), fn ($o) => is_array($o)
            && preg_match('/^[a-z][a-z0-9_]{0,40}$/', (string) ($o['name'] ?? ''))
            && in_array($o['type'] ?? 'text', ['text', 'textarea', 'toggle', 'select', 'color', 'media'], true)));
    }

    /** @return string[] */
    private function assets(string $type): array
    {
        $files = (array) ($this->manifest['assets'][$type] ?? []);

        return array_values(array_map(
            fn ($f) => $this->assetUrl($f),
            array_filter($files, fn ($f) => is_string($f) && str_ends_with($f, '.'.$type) && is_file($this->path.'/'.ltrim($f, '/')))
        ));
    }
}
