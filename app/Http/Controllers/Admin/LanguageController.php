<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Languages\LanguageManager;
use App\Cms\Packages\PackageException;
use App\Cms\SiteSettings;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Languages: languages of the public site, default language, and language
 * packs (installation from a Git repository or a ZIP archive).
 */
class LanguageController extends Controller
{
    public function index(LanguageManager $languages): View
    {
        return view('admin.languages.index', [
            'packs' => $languages->all(),
            'manager' => $languages,
            'siteLocales' => $languages->siteLocales(),
            'defaultLocale' => $languages->defaultLocale(),
            'adminLocale' => app(SiteSettings::class)->get('admin_locale') ?: $languages->defaultLocale(),
            'pageCounts' => Page::selectRaw('locale, count(*) as total')->groupBy('locale')->pluck('total', 'locale'),
            'canInstall' => config('mycms.packages.allow_admin_install'),
        ]);
    }

    /** Languages of the site and default languages. */
    public function save(Request $request, LanguageManager $languages, SiteSettings $settings): RedirectResponse
    {
        $codes = array_keys($languages->all());
        $data = $request->validate([
            'site_locales' => ['nullable', 'array'],
            'site_locales.*' => [Rule::in($codes)],
            'default_locale' => ['required', Rule::in($codes)],
            'admin_locale' => ['required', Rule::in($codes)],
        ]);

        $default = $data['default_locale'];
        $site = array_values(array_unique([$default, ...($data['site_locales'] ?? [])]));

        // The default language needs a home page
        $previousDefault = $languages->defaultLocale();
        if ($default !== $previousDefault && ! Page::inLocale($default)->where('is_home', true)->exists()) {
            return back()->withErrors(['default_locale' => __('Create the home page in this language first (Pages → translate the home page).')]);
        }

        $settings->set(['site_locales' => $site, 'default_locale' => $default, 'admin_locale' => $data['admin_locale']]);
        ActivityLog::record('languages.updated', __('Site languages changed: :list', ['list' => implode(', ', $site)]));

        return redirect()->route('admin.languages.index')->with('status', __('Languages saved.'));
    }

    public function install(Request $request, LanguageManager $languages): RedirectResponse
    {
        abort_unless(config('mycms.packages.allow_admin_install'), 403);
        $request->validate([
            'source' => ['required', 'in:url,zip'],
            'url' => ['required_if:source,url', 'nullable', 'string', 'max:500'],
            'zip' => ['required_if:source,zip', 'nullable', 'file', 'mimes:zip', 'max:'.(config('mycms.packages.max_download_mb') * 1024)],
        ], [], ['url' => __('address'), 'zip' => __('ZIP file')]);

        try {
            $pack = $request->input('source') === 'url'
                ? $languages->install(['url' => $request->input('url')])
                : $languages->install(['zip' => $request->file('zip')->getRealPath()]);
        } catch (PackageException $e) {
            return back()->withInput()->withErrors(['install' => $e->getMessage()]);
        }

        ActivityLog::record('language.installed', __('Language installed: :name', ['name' => $pack->name()]));

        return redirect()->route('admin.languages.index')->with('status', __('The language ":name" is installed.', ['name' => $pack->nativeName()]));
    }

    public function update(LanguageManager $languages, string $code): RedirectResponse
    {
        abort_unless(config('mycms.packages.allow_admin_install'), 403);
        $pack = $languages->find($code) ?? abort(404);
        $url = $languages->sourceUrl($pack) ?? abort(404);

        try {
            $updated = $languages->install(['url' => $url]);
        } catch (PackageException $e) {
            return back()->withErrors(['install' => $e->getMessage()]);
        }
        ActivityLog::record('language.updated', __('Language updated: :name', ['name' => $updated->name()]));

        return back()->with('status', __('The language ":name" is up to date.', ['name' => $updated->nativeName()]));
    }

    public function destroy(LanguageManager $languages, string $code): RedirectResponse
    {
        abort_unless(config('mycms.packages.allow_admin_install'), 403);
        $name = $languages->find($code)?->nativeName() ?? $code;

        try {
            $languages->delete($code);
        } catch (PackageException $e) {
            return back()->withErrors(['language' => $e->getMessage()]);
        }
        ActivityLog::record('language.deleted', __('Language deleted: :name', ['name' => $name]));

        return back()->with('status', __('The language ":name" has been deleted.', ['name' => $name]));
    }
}
