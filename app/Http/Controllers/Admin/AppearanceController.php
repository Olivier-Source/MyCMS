<?php

namespace App\Http\Controllers\Admin;

use App\Cms\ContentSanitizer;
use App\Cms\SiteSettings;
use App\Cms\ThemePalette;
use App\Cms\Themes\ThemeManager;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Appearance": colours of the site with a live preview, and the options of
 * the active theme.
 */
class AppearanceController extends Controller
{
    public function edit(SiteSettings $settings, ThemeManager $themes): View
    {
        $theme = $themes->active();
        $options = $theme->options();
        $values = (array) $settings->get('theme_options.'.$theme->slug, []);
        $mediaIds = collect($options)->where('type', 'media')->map(fn ($o) => $values[$o['name']] ?? null)->filter()->all();

        return view('admin.appearance.edit', [
            'theme' => $theme,
            'colors' => ThemePalette::sanitize($settings->get('palette') ?: ThemePalette::defaults($theme)),
            'fields' => ThemePalette::fields(),
            'presets' => ThemePalette::presets($theme),
            'default' => ThemePalette::defaults($theme),
            'options' => $options,
            'optionValues' => $values,
            'media' => Media::whereIn('id', $mediaIds)->get(),
        ]);
    }

    public function update(Request $request, SiteSettings $settings): RedirectResponse
    {
        $rules = [];
        foreach (ThemePalette::KEYS as $key) {
            $rules["colors.$key"] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }
        $data = $request->validate($rules, ['colors.*.regex' => __('Invalid colour (#RRGGBB format expected).')]);

        $settings->set(['palette' => ThemePalette::sanitize($data['colors'])]);
        ActivityLog::record('theme.colors', __('Site colours changed'));

        return redirect()->route('admin.appearance.edit')->with('status', __('New colours saved: the site is up to date.'));
    }

    /** Options declared by the active theme (theme.json → "options"). */
    public function updateOptions(Request $request, SiteSettings $settings, ThemeManager $themes, ContentSanitizer $sanitizer): RedirectResponse
    {
        $theme = $themes->active();
        $values = [];

        foreach ($theme->options() as $option) {
            $name = $option['name'];
            $input = $request->input('options.'.$name);
            $values[$name] = match ($option['type']) {
                'toggle' => $request->boolean('options.'.$name),
                'select' => array_key_exists((string) $input, (array) ($option['options'] ?? [])) ? (string) $input : ($option['default'] ?? null),
                'color' => is_string($input) && ThemePalette::isHex($input) ? strtolower($input) : ($option['default'] ?? null),
                'media' => $input && Media::whereKey((int) $input)->exists() ? (int) $input : null,
                'textarea' => $sanitizer->plain($input, 2000, true),
                default => $sanitizer->plain($input, 500),
            };
        }

        $all = (array) $settings->get('theme_options', []);
        $all[$theme->slug] = $values;
        $settings->set(['theme_options' => $all]);
        ActivityLog::record('theme.options', __('Theme options changed (:theme)', ['theme' => $theme->name()]));

        return redirect()->route('admin.appearance.edit')->with('status', __('Theme options saved.'));
    }
}
