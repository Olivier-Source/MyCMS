<?php

namespace App\Http\Controllers\Admin;

use App\Cms\ContentSanitizer;
use App\Cms\Languages\LanguageManager;
use App\Cms\Placeholders;
use App\Cms\SiteSettings;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Site information": all the central information, by group. When the site
 * has several languages, translatable fields can be filled in for each one.
 */
class SettingsController extends Controller
{
    public function edit(Request $request, SiteSettings $settings, LanguageManager $languages, ?string $group = null): View
    {
        $groups = SiteSettings::groups();
        $group ??= array_key_first($groups);
        abort_unless(isset($groups[$group]), 404);

        $lang = $this->editedLocale($request, $languages);
        $fields = $groups[$group]['fields'];

        $mediaIds = collect($fields)->filter(fn ($f) => $f['type'] === 'media')->keys()
            ->map(fn ($key) => $settings->get($key))->filter()->all();

        return view('admin.settings.edit', [
            'groups' => $groups,
            'current' => $group,
            'values' => $settings->all(),
            'media' => Media::whereIn('id', $mediaIds)->get()->keyBy('id'),
            'tags' => Placeholders::catalog(),
            'lang' => $lang,
            'siteLocales' => $languages->siteLocales(),
            'defaultLocale' => $languages->defaultLocale(),
            'languages' => $languages,
            'translations' => $lang ? (array) ($settings->all()['translations'][$lang] ?? []) : [],
        ]);
    }

    public function update(Request $request, SiteSettings $settings, ContentSanitizer $sanitizer, LanguageManager $languages, string $group): RedirectResponse
    {
        $groups = SiteSettings::groups();
        abort_unless(isset($groups[$group]), 404);

        $lang = $this->editedLocale($request, $languages);
        $fields = $groups[$group]['fields'];
        if ($lang) {
            // Translation of the site information into another language
            $fields = array_filter($fields, fn ($f) => $f['translatable'] ?? false);
        }

        $rules = [];
        foreach ($fields as $key => $field) {
            $required = ($field['required'] ?? false) && ! $lang ? 'required' : 'nullable';
            $rules[$key] = match ($field['type']) {
                'email' => [$required, 'email:rfc', 'max:190'],
                'url' => [$required, 'url:https', 'max:500'],
                'link' => [$required, 'string', 'max:500'],
                'number' => [$required, 'numeric', 'between:-180,180'],
                'date' => [$required, 'date_format:Y-m-d'],
                'media' => [$required, 'integer', 'exists:media,id'],
                'toggle' => ['nullable'],
                'icon' => ['nullable', 'regex:/^[a-z0-9_]{1,40}$/'],
                'hours', 'links' => ['nullable', 'array', 'max:20'],
                'textarea' => [$required, 'string', 'max:'.($field['max'] ?? 2000)],
                default => [$required, 'string', 'max:'.($field['max'] ?? 250)],
            };
            if ($field['type'] === 'hours') {
                $rules[$key.'.*.label'] = ['nullable', 'string', 'max:60'];
                $rules[$key.'.*.value'] = ['nullable', 'string', 'max:80'];
            }
            if ($field['type'] === 'links') {
                $rules[$key.'.*.label'] = ['nullable', 'string', 'max:40'];
                $rules[$key.'.*.url'] = ['nullable', 'url:https,http', 'max:300'];
            }
        }

        $labels = collect($fields)->map(fn ($f) => mb_strtolower($f['label']))->all();
        $validated = $request->validate($rules, [], $labels);

        $values = [];
        $errors = [];
        foreach ($fields as $key => $field) {
            $value = $validated[$key] ?? null;
            $values[$key] = match ($field['type']) {
                'toggle' => $request->boolean($key),
                'media' => $value ? (int) $value : null,
                'hours' => collect($value ?? [])
                    ->map(fn ($row) => ['label' => $sanitizer->plain($row['label'] ?? '', 60), 'value' => $sanitizer->plain($row['value'] ?? '', 80)])
                    ->filter(fn ($row) => $row['label'] !== '' || $row['value'] !== '')
                    ->values()->all(),
                'links' => collect($value ?? [])
                    ->map(fn ($row) => ['label' => $sanitizer->plain($row['label'] ?? '', 40), 'url' => trim((string) ($row['url'] ?? ''))])
                    ->filter(fn ($row) => $row['label'] !== '' && $row['url'] !== '')
                    ->values()->all(),
                'link' => $sanitizer->link($value),
                'icon' => (string) $value,
                'textarea' => $sanitizer->plain($value, $field['max'] ?? 2000, true),
                default => $sanitizer->plain($value, $field['max'] ?? 500),
            };
            if ($values[$key] === false) {
                $errors[$key] = __('Invalid address: use an internal link (/my-page), https://…, tel:… or mailto:…');
            }
        }
        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $lang ? $settings->setTranslations($lang, $values) : $settings->set($values);
        ActivityLog::record('settings.updated', __('Site information changed: :group', ['group' => $groups[$group]['label']]).($lang ? ' ('.$lang.')' : ''));

        return redirect()->route('admin.settings.edit', ['group' => $group, 'lang' => $lang])
            ->with('status', __('Information saved: it is up to date on the whole site.'));
    }

    /** Language being translated (null = default language). */
    private function editedLocale(Request $request, LanguageManager $languages): ?string
    {
        $lang = $request->query('lang', $request->input('lang'));

        return is_string($lang) && $lang !== $languages->defaultLocale() && in_array($lang, $languages->siteLocales(), true) ? $lang : null;
    }
}
