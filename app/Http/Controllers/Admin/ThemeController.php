<?php

namespace App\Http\Controllers\Admin;

use App\Cms\BlockRegistry;
use App\Cms\Packages\PackageException;
use App\Cms\Themes\ThemeManager;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Themes: list, activation, installation from a Git repository or a ZIP
 * archive, update and deletion.
 */
class ThemeController extends Controller
{
    public function index(ThemeManager $themes): View
    {
        return view('admin.themes.index', [
            'themes' => $themes->all(),
            'active' => $themes->active(),
            'manager' => $themes,
            'canInstall' => config('mycms.packages.allow_admin_install'),
        ]);
    }

    public function activate(ThemeManager $themes, string $theme): RedirectResponse
    {
        try {
            $themes->activate($theme);
        } catch (PackageException $e) {
            return back()->withErrors(['theme' => $e->getMessage()]);
        }
        BlockRegistry::flush();
        $name = $themes->find($theme)->name();
        ActivityLog::record('theme.activated', __('Theme activated: :name', ['name' => $name]));

        return redirect()->route('admin.themes.index')->with('status', __('The theme ":name" is now used by the site.', ['name' => $name]));
    }

    public function install(Request $request, ThemeManager $themes): RedirectResponse
    {
        abort_unless(config('mycms.packages.allow_admin_install'), 403);
        $request->validate([
            'source' => ['required', 'in:url,zip'],
            'url' => ['required_if:source,url', 'nullable', 'string', 'max:500'],
            'zip' => ['required_if:source,zip', 'nullable', 'file', 'mimes:zip', 'max:'.(config('mycms.packages.max_download_mb') * 1024)],
        ], [], ['url' => __('address'), 'zip' => __('ZIP file')]);

        try {
            $theme = $request->input('source') === 'url'
                ? $themes->install(['url' => $request->input('url')])
                : $themes->install(['zip' => $request->file('zip')->getRealPath()]);
        } catch (PackageException $e) {
            return back()->withInput()->withErrors(['install' => $e->getMessage()]);
        }

        ActivityLog::record('theme.installed', __('Theme installed: :name :version', ['name' => $theme->name(), 'version' => $theme->version()]));

        return redirect()->route('admin.themes.index')->with('status', __('The theme ":name" is installed. Activate it to use it on the site.', ['name' => $theme->name()]));
    }

    public function update(ThemeManager $themes, string $theme): RedirectResponse
    {
        abort_unless(config('mycms.packages.allow_admin_install'), 403);
        $current = $themes->find($theme) ?? abort(404);
        $url = $themes->sourceUrl($current) ?? abort(404);

        try {
            $updated = $themes->install(['url' => $url]);
        } catch (PackageException $e) {
            return back()->withErrors(['install' => $e->getMessage()]);
        }
        BlockRegistry::flush();
        ActivityLog::record('theme.updated', __('Theme updated: :name :version', ['name' => $updated->name(), 'version' => $updated->version()]));

        return back()->with('status', __('The theme ":name" is up to date (version :version).', ['name' => $updated->name(), 'version' => $updated->version()]));
    }

    public function destroy(ThemeManager $themes, string $theme): RedirectResponse
    {
        abort_unless(config('mycms.packages.allow_admin_install'), 403);
        $name = $themes->find($theme)?->name() ?? $theme;

        try {
            $themes->delete($theme);
        } catch (PackageException $e) {
            return back()->withErrors(['theme' => $e->getMessage()]);
        }
        ActivityLog::record('theme.deleted', __('Theme deleted: :name', ['name' => $name]));

        return back()->with('status', __('The theme ":name" has been deleted.', ['name' => $name]));
    }
}
