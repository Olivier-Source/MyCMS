<?php

namespace App\Http\Controllers\Site;

use App\Cms\Languages\LanguageManager;
use App\Cms\SiteSettings;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(SiteSettings $settings): Response
    {
        $body = $settings->get('allow_indexing')
            ? "User-agent: *\nAllow: /\n\nSitemap: ".route('sitemap')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(LanguageManager $languages): Response
    {
        $pages = Page::published()->whereIn('locale', $languages->siteLocales())->orderBy('locale')->orderBy('position')->get();
        $groups = $pages->groupBy(fn (Page $p) => $p->translationGroup());

        return response()
            ->view('seo.sitemap', ['pages' => $pages, 'groups' => $groups, 'languages' => $languages])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
