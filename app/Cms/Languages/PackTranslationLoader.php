<?php

namespace App\Cms\Languages;

use App\Cms\Themes\Theme;
use App\Cms\Themes\ThemeManager;
use Illuminate\Translation\FileLoader;

/**
 * Adds language packs and theme translations to Laravel's translations:
 *  - "JSON" strings (__('Save')) : messages.json of the pack + lang/{locale}.json of the themes;
 *  - groups (validation, passwords…) : {group}.json of the pack.
 *
 * Packs and themes are resolved lazily (on the first translation), because
 * they depend on the site settings stored in the database.
 */
class PackTranslationLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null)
    {
        $lines = parent::load($locale, $group, $namespace);

        if ($namespace !== null && $namespace !== '*') {
            return $lines;
        }

        $pack = app(LanguageManager::class)->find($locale);

        if ($group === '*') {
            $extra = [];
            // Theme strings first: the language pack may refine them
            foreach (array_reverse($this->themes()) as $theme) {
                $data = json_decode((string) @file_get_contents($theme->langFile($locale)), true);
                $extra = array_replace($extra, is_array($data) ? $data : []);
            }

            return array_replace($extra, $pack?->file('messages') ?? [], $lines);
        }

        return array_replace_recursive($pack?->file($group) ?? [], $lines);
    }

    /**
     * Active theme and its parents first (their strings win), then the other
     * installed themes (their name and description in the themes list).
     *
     * @return Theme[]
     */
    private function themes(): array
    {
        try {
            $manager = app(ThemeManager::class);
            $chain = $manager->chain();

            return [...$chain, ...array_diff_key($manager->all(), array_flip(array_map(fn ($t) => $t->slug, $chain)))];
        } catch (\Throwable) {
            return [];
        }
    }
}
