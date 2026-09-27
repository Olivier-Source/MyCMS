<?php

namespace App\Http\Controllers\Site;

use App\Cms\Languages\LanguageManager;
use App\Cms\Renderer;
use App\Cms\SiteSettings;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /**
     * "/", "/about" (default language) — "/fr", "/fr/about" (other languages).
     */
    public function show(LanguageManager $languages, ?string $path = null): View
    {
        $segments = $path ? explode('/', $path) : [];
        $locale = $languages->defaultLocale();

        if ($segments && $prefixed = $languages->localeFromPrefix($segments[0])) {
            $locale = $prefixed;
            array_shift($segments);
        }
        abort_if(count($segments) > 1, 404);

        $query = Page::inLocale($locale);
        $page = $segments
            ? $query->published()->where('slug', $segments[0])->where('is_home', false)->first()
            : $query->where('is_home', true)->first();

        abort_unless($page, 404);

        return self::render($page);
    }

    public static function render(Page $page, bool $preview = false): View
    {
        app(SiteSettings::class)->setContentLocale($page->locale);
        app()->setLocale($page->locale);

        $blocks = $page->visibleBlocks()->get();

        return view('theme::site.page', [
            'page' => $page,
            'blocks' => $blocks,
            'r' => app(Renderer::class)->preload($blocks),
            'preview' => $preview,
            'alternates' => self::alternates($page),
        ]);
    }

    /**
     * @return array<string, string> locale => URL of the same page in the other
     *                               languages of the site (home page otherwise)
     */
    public static function alternates(Page $page): array
    {
        $languages = app(LanguageManager::class);
        if (! $languages->isMultilingual()) {
            return [];
        }

        $translations = $page->translations()->where('is_published', true)->keyBy('locale');
        $homes = Page::where('is_home', true)->get()->keyBy('locale');
        $urls = [];
        foreach ($languages->siteLocales() as $code) {
            $target = $translations[$code] ?? $homes[$code] ?? null;
            if ($target) {
                $urls[$code] = $target->url();
            }
        }

        return $urls;
    }
}
