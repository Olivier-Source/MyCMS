<?php

namespace App\Cms\Languages;

use App\Cms\Packages\PackageException;
use App\Cms\Packages\PackageFetcher;
use App\Cms\SiteSettings;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\File;

/**
 * Language packs (interface translations) and languages of the public site.
 *
 * - English is the source language of MyCMS: it is always available.
 * - Other languages come from language packs: bundled in resources/languages,
 *   or installed from a Git repository / ZIP archive into storage/app/languages.
 * - The site can be published in several languages: the default language is
 *   served at the root (/about), the others under a prefix (/fr/about).
 */
class LanguageManager
{
    public const MANIFEST = 'language.json';

    public const EXTENSIONS = ['json', 'md', 'txt'];

    /** @var array<string, LanguagePack>|null */
    private ?array $packs = null;

    public function __construct(private SiteSettings $settings, private PackageFetcher $fetcher) {}

    public static function bundledPath(): string
    {
        return resource_path('languages');
    }

    public static function installedPath(): string
    {
        return storage_path('app/languages');
    }

    /** @return array<string, LanguagePack> Installed languages, English first */
    public function all(): array
    {
        if ($this->packs !== null) {
            return $this->packs;
        }

        $packs = ['en' => new LanguagePack('en', null, ['name' => 'English', 'native' => 'English', 'og_locale' => 'en_US'], true)];
        foreach ([self::bundledPath() => true, self::installedPath() => false] as $root => $bundled) {
            foreach (glob($root.'/*/'.self::MANIFEST) ?: [] as $manifest) {
                $dir = dirname($manifest);
                $code = basename($dir);
                $data = json_decode((string) file_get_contents($manifest), true);
                if (! self::validCode($code) || ! is_array($data) || ($packs[$code]->bundled ?? false) && ! $bundled) {
                    continue;
                }
                $packs[$code] = new LanguagePack($code, str_replace('\\', '/', $dir), $data, $bundled);
            }
        }

        return $this->packs = $packs;
    }

    public function find(?string $code): ?LanguagePack
    {
        return $code ? ($this->all()[$code] ?? null) : null;
    }

    public function exists(?string $code): bool
    {
        return $this->find($code) !== null;
    }

    /** Default language of the public site */
    public function defaultLocale(): string
    {
        $code = $this->settings->get('default_locale') ?: config('app.locale');

        return $this->exists($code) ? $code : 'en';
    }

    /** @return string[] Languages the public site is published in (default first) */
    public function siteLocales(): array
    {
        $default = $this->defaultLocale();
        $enabled = array_filter((array) $this->settings->get('site_locales', []), fn ($c) => is_string($c) && $this->exists($c));

        return array_values(array_unique([$default, ...$enabled]));
    }

    public function isMultilingual(): bool
    {
        return count($this->siteLocales()) > 1;
    }

    /** Site language matching a URL prefix ("fr", "pt-br"), default language excluded. */
    public function localeFromPrefix(string $prefix): ?string
    {
        foreach ($this->siteLocales() as $code) {
            if ($code !== $this->defaultLocale() && $this->find($code)->urlPrefix() === $prefix) {
                return $code;
            }
        }

        return null;
    }

    /** Path prefix of a site language ("" for the default language, "/fr" otherwise). */
    public function pathPrefix(string $code): string
    {
        return $code === $this->defaultLocale() ? '' : '/'.($this->find($code)?->urlPrefix() ?? $code);
    }

    /** Language of the administration for a user. */
    public function adminLocale(?User $user): string
    {
        $code = $user?->locale ?: $this->settings->get('admin_locale') ?: config('app.locale');

        return $this->exists($code) ? $code : 'en';
    }

    /** @return array<string, string> code => native name, for select menus */
    public function options(): array
    {
        return array_map(fn (LanguagePack $p) => $p->nativeName(), $this->all());
    }

    /**
     * Installs (or updates) a language pack.
     *
     * @param  array{url?: string, zip?: string}  $source
     */
    public function install(array $source): LanguagePack
    {
        $tmp = isset($source['url']) ? $this->fetcher->fromUrl($source['url']) : $this->fetcher->fromZip($source['zip']);
        $staging = null;

        try {
            $root = $this->fetcher->locateRoot($tmp, self::MANIFEST);
            $manifest = json_decode((string) file_get_contents($root.'/'.self::MANIFEST), true);
            $code = is_array($manifest) ? (string) ($manifest['code'] ?? '') : '';
            if (! self::validCode($code) || empty($manifest['name'])) {
                throw new PackageException(__('The language.json file is invalid: "code" (e.g. "de" or "pt_BR") and "name" are required.'));
            }
            if ($code === 'en' || ($this->find($code)?->bundled ?? false)) {
                throw new PackageException(__('The language ":code" is built in and cannot be replaced.', ['code' => $code]));
            }
            foreach (glob($root.'/*.json') ?: [] as $file) {
                if (! is_array(json_decode((string) file_get_contents($file), true))) {
                    throw new PackageException(__('The file :file is not valid JSON.', ['file' => basename($file)]));
                }
            }

            $staging = $this->fetcher->tempDir();
            $this->fetcher->copyAllowed($root, $staging, self::EXTENSIONS);
            File::put($staging.'/.source.json', json_encode([
                'url' => $source['url'] ?? null,
                'installed_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $target = self::installedPath().'/'.$code;
            File::ensureDirectoryExists(self::installedPath());
            File::deleteDirectory($target);
            if (! File::moveDirectory($staging, $target)) {
                throw new PackageException(__('The language pack could not be copied into storage/app/languages (check permissions).'));
            }
            $staging = null;
        } finally {
            $this->fetcher->cleanup($tmp, $staging);
        }

        $this->packs = null;
        app('translator')->setLoaded([]);

        return $this->find($code);
    }

    public function sourceUrl(LanguagePack $pack): ?string
    {
        $info = $pack->path ? json_decode((string) @file_get_contents($pack->path.'/.source.json'), true) : null;

        return is_array($info) && is_string($info['url'] ?? null) ? $info['url'] : null;
    }

    public function delete(string $code): void
    {
        $pack = $this->find($code) ?? throw new PackageException(__('This language is not installed.'));
        if ($pack->bundled) {
            throw new PackageException(__('Built-in languages cannot be deleted.'));
        }
        if (in_array($code, $this->siteLocales(), true)) {
            throw new PackageException(__('This language is used by the site: remove it from the site languages first.'));
        }
        if (Page::where('locale', $code)->exists()) {
            throw new PackageException(__('Pages are written in this language: delete them first.'));
        }

        User::where('locale', $code)->update(['locale' => null]);
        File::deleteDirectory($pack->path);
        $this->packs = null;
    }

    public static function validCode(string $code): bool
    {
        return (bool) preg_match('/^[a-z]{2,3}(_[A-Z][A-Za-z]{1,3})?$/', $code);
    }
}
