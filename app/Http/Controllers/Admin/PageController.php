<?php

namespace App\Http\Controllers\Admin;

use App\Cms\BlockRegistry;
use App\Cms\ContentSanitizer;
use App\Cms\Languages\LanguageManager;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\PageController as SitePageController;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    /** Addresses reserved by the system */
    public const RESERVED_SLUGS = ['admin', 'media', 'themes', 'contact', 'robots', 'sitemap', 'up', 'storage', 'build', 'login', 'api', 'vendor'];

    /** @return array<string, array> Templates offered when creating a page */
    public static function templates(): array
    {
        return [
            'text' => ['label' => __('Text page'), 'icon' => 'article', 'description' => __('A title and a free text (article, information…).'), 'blocks' => ['page_header', 'rich_text']],
            'presentation' => ['label' => __('Presentation page'), 'icon' => 'web', 'description' => __('Hero header, text with picture, cards and call to action.'), 'blocks' => ['hero', 'text_image', 'cards', 'cta']],
            'contact' => ['label' => __('Contact page'), 'icon' => 'contact_mail', 'description' => __('A title, your contact details and the contact form.'), 'blocks' => ['page_header', 'contact_cards', 'contact_form']],
            'faq' => ['label' => __('Questions page'), 'icon' => 'quiz', 'description' => __('A title and a list of questions / answers.'), 'blocks' => ['page_header', 'faq']],
            'blank' => ['label' => __('Empty page'), 'icon' => 'note_add', 'description' => __('You add the sections yourself.'), 'blocks' => []],
        ];
    }

    public function index(Request $request, LanguageManager $languages): View
    {
        $locale = $this->currentLocale($request, $languages);

        return view('admin.pages.index', [
            'pages' => Page::withCount('blocks')->inLocale($locale)->orderBy('position')->get(),
            'locale' => $locale,
            'languages' => $languages,
            'counts' => Page::selectRaw('locale, count(*) as total')->groupBy('locale')->pluck('total', 'locale'),
        ]);
    }

    public function create(Request $request, LanguageManager $languages): View
    {
        return view('admin.pages.create', [
            'templates' => self::templates(),
            'locale' => $this->currentLocale($request, $languages),
            'languages' => $languages,
        ]);
    }

    public function store(Request $request, LanguageManager $languages): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80'],
            'template' => ['required', Rule::in(array_keys(self::templates()))],
            'locale' => ['required', Rule::in($languages->siteLocales())],
        ], [], ['title' => __('title'), 'slug' => __('address')]);

        $slug = $this->uniqueSlug(($data['slug'] ?? null) ?: $data['title'], $data['locale']);

        $page = DB::transaction(function () use ($data, $slug) {
            $page = Page::create([
                'title' => strip_tags($data['title']),
                'slug' => $slug,
                'locale' => $data['locale'],
                'is_published' => false,
                'show_in_nav' => false,
                'position' => (int) Page::inLocale($data['locale'])->max('position') + 1,
            ]);

            foreach (self::templates()[$data['template']]['blocks'] as $i => $type) {
                $defaults = BlockRegistry::defaults($type);
                if (in_array($type, ['hero', 'page_header', 'cta'], true)) {
                    $defaults['title'] = $page->title;
                }
                $page->blocks()->create(['type' => $type, 'position' => $i, 'data' => $defaults]);
            }

            return $page;
        });

        ActivityLog::record('page.created', __('Page created: :title', ['title' => $page->title]));

        return redirect()->route('admin.pages.edit', $page)
            ->with('status', __('Page created. It is not visible on the site yet: publish it when it is ready.'));
    }

    public function edit(Page $page, LanguageManager $languages): View
    {
        return view('admin.pages.edit', [
            'page' => $page,
            'blocks' => $page->blocks()->get(),
            'registry' => BlockRegistry::all(),
            'languages' => $languages,
            'translations' => $page->translations()->keyBy('locale'),
        ]);
    }

    public function settings(Page $page): View
    {
        return view('admin.pages.settings', [
            'page' => $page,
            'ogImage' => $page->og_image_id ? Media::find($page->og_image_id) : null,
        ]);
    }

    public function update(Request $request, Page $page, ContentSanitizer $sanitizer, LanguageManager $languages): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => $page->is_home ? ['nullable'] : [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn([...self::RESERVED_SLUGS, ...$this->localePrefixes($languages)]),
                Rule::unique('pages', 'slug')->where('locale', $page->locale)->ignore($page->id),
            ],
            'nav_label' => ['nullable', 'string', 'max:40'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'og_image_id' => ['nullable', 'integer', 'exists:media,id'],
        ], [
            'slug.regex' => __('The address may only contain lowercase letters without accents, digits and dashes.'),
            'slug.not_in' => __('This address is reserved by the system.'),
        ], ['title' => __('title'), 'slug' => __('address')]);

        $page->fill([
            'title' => $sanitizer->plain($data['title'], 120),
            'nav_label' => $sanitizer->plain($data['nav_label'] ?? '', 40) ?: null,
            'meta_title' => $sanitizer->plain($data['meta_title'] ?? '', 70) ?: null,
            'meta_description' => $sanitizer->plain($data['meta_description'] ?? '', 300) ?: null,
            'og_image_id' => $data['og_image_id'] ?? null,
            'show_in_nav' => $request->boolean('show_in_nav'),
            'show_in_footer' => $request->boolean('show_in_footer'),
        ]);

        if (! $page->is_home) {
            $page->slug = $data['slug'];
            $page->is_published = $request->boolean('is_published');
        }

        $page->save();
        ActivityLog::record('page.updated', __('Settings changed: :title', ['title' => $page->title]));

        return redirect()->route('admin.pages.settings', $page)->with('status', __('Settings saved.'));
    }

    public function togglePublish(Page $page): RedirectResponse
    {
        abort_if($page->is_home, 403, __('The home page is always published.'));

        $page->update(['is_published' => ! $page->is_published]);
        ActivityLog::record('page.published', ($page->is_published ? __('Page published: :title', ['title' => $page->title]) : __('Page disabled: :title', ['title' => $page->title])));

        return back()->with('status', $page->is_published
            ? __('":title" is now visible on the site.', ['title' => $page->title])
            : __('":title" is disabled: it is no longer visible on the site (it can still be edited here).', ['title' => $page->title]));
    }

    public function duplicate(Page $page): RedirectResponse
    {
        $copy = $this->copy($page, $page->locale, __(':title (copy)', ['title' => $page->title]), $page->slug.'-copy', false);
        ActivityLog::record('page.duplicated', __('Page duplicated: :title', ['title' => $page->title]));

        return redirect()->route('admin.pages.edit', $copy)->with('status', __('Copy created (not published).'));
    }

    /**
     * Creates the translation of a page into another language of the site:
     * a copy of the page and its sections, linked to the original, to be
     * translated section by section.
     */
    public function translate(Request $request, Page $page, LanguageManager $languages): RedirectResponse
    {
        $locale = $request->validate(['locale' => ['required', Rule::in($languages->siteLocales())]])['locale'];

        $existing = $page->translations()->firstWhere('locale', $locale);
        if ($existing) {
            return redirect()->route('admin.pages.edit', $existing);
        }

        $copy = $this->copy($page, $locale, $page->title, $page->slug, $page->is_home);
        ActivityLog::record('page.translated', __('Translation created: :title (:locale)', ['title' => $page->title, 'locale' => $locale]));

        return redirect()->route('admin.pages.edit', $copy)
            ->with('status', __('Translation created: translate the title and each section, then publish the page.'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        abort_if($page->is_home && $page->locale === app(LanguageManager::class)->defaultLocale(), 403, __('The home page cannot be deleted.'));

        $title = $page->title;
        $locale = $page->locale;
        DB::transaction(function () use ($page) {
            // Translations of a deleted original are linked to the first remaining translation
            $others = Page::where('translation_of', $page->id)->orderBy('id')->get();
            if ($first = $others->shift()) {
                $first->update(['translation_of' => null]);
                Page::whereIn('id', $others->pluck('id'))->update(['translation_of' => $first->id]);
            }
            $page->delete();
        });
        ActivityLog::record('page.deleted', __('Page deleted: :title', ['title' => $title]));

        return redirect()->route('admin.pages.index', ['lang' => $locale])->with('status', __('":title" has been deleted.', ['title' => $title]));
    }

    public function preview(Page $page): View
    {
        return SitePageController::render($page, true);
    }

    public function reorder(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $i => $id) {
                Page::whereKey($id)->update(['position' => $i]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /* ------------------------------------------------------------------ */

    private function copy(Page $page, string $locale, string $title, string $slug, bool $isHome): Page
    {
        return DB::transaction(function () use ($page, $locale, $title, $slug, $isHome) {
            $copy = $page->replicate(['is_home', 'translation_of']);
            $copy->title = $title;
            $copy->locale = $locale;
            $copy->slug = $this->uniqueSlug($slug, $locale);
            $copy->is_home = $isHome && ! Page::inLocale($locale)->where('is_home', true)->exists();
            $copy->translation_of = $locale !== $page->locale ? $page->translationGroup() : null;
            $copy->is_published = $copy->is_home;
            $copy->show_in_nav = $locale !== $page->locale && $page->show_in_nav;
            $copy->show_in_footer = $locale !== $page->locale && $page->show_in_footer;
            $copy->position = $locale !== $page->locale ? $page->position : (int) Page::inLocale($locale)->max('position') + 1;
            $copy->save();

            foreach ($page->blocks as $block) {
                $copy->blocks()->create($block->only(['type', 'position', 'is_visible', 'data']));
            }

            return $copy;
        });
    }

    private function currentLocale(Request $request, LanguageManager $languages): string
    {
        $lang = $request->query('lang');

        return is_string($lang) && in_array($lang, $languages->siteLocales(), true) ? $lang : $languages->defaultLocale();
    }

    /** @return string[] URL prefixes of the languages (cannot be used as page addresses) */
    private function localePrefixes(LanguageManager $languages): array
    {
        return array_map(fn ($p) => $p->urlPrefix(), $languages->all());
    }

    private function uniqueSlug(string $source, string $locale): string
    {
        $base = Str::slug($source) ?: 'page';
        if (in_array($base, [...self::RESERVED_SLUGS, ...$this->localePrefixes(app(LanguageManager::class))], true)) {
            $base .= '-page';
        }
        $slug = $base;
        $i = 2;
        while (Page::inLocale($locale)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
