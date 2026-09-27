<?php

namespace App\Http\Controllers\Admin;

use App\Cms\ContentSanitizer;
use App\Cms\MediaStore;
use App\Cms\SiteSettings;
use App\Cms\Themes\ThemeManager;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Block;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(): View
    {
        return view('admin.media.index', [
            'items' => Media::latest()->paginate(36),
            'gdAvailable' => extension_loaded('gd'),
        ]);
    }

    /** JSON list for the image picker of the forms. */
    public function list(): JsonResponse
    {
        return response()->json(Media::latest()->limit(300)->get()->map(fn (Media $m) => $this->payload($m)));
    }

    public function store(Request $request, MediaStore $store, ContentSanitizer $sanitizer): JsonResponse|RedirectResponse
    {
        $maxMb = (int) round(config('mycms.media.max_kb') / 1024);
        $request->validate([
            'file' => ['required', 'file', 'max:'.config('mycms.media.max_kb'), 'mimes:'.implode(',', config('mycms.media.mimes')), 'mimetypes:image/jpeg,image/png,image/webp'],
            'alt' => ['nullable', 'string', 'max:200'],
        ], [
            'file.max' => __('The image is too heavy (:max MB maximum).', ['max' => $maxMb]),
            'file.mimes' => __('Accepted formats: JPG, PNG or WebP.'),
            'file.mimetypes' => __('Accepted formats: JPG, PNG or WebP.'),
        ]);

        try {
            $media = $store->store($request->file('file'), $sanitizer->plain($request->input('alt'), 200) ?: null);
        } catch (\RuntimeException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->withErrors(['file' => $e->getMessage()]);
        }

        ActivityLog::record('media.uploaded', __('Image added: :name', ['name' => $media->original_name]));

        return $request->expectsJson()
            ? response()->json($this->payload($media), 201)
            : back()->with('status', __('Image added to the media library.'));
    }

    public function update(Request $request, Media $media, ContentSanitizer $sanitizer): RedirectResponse|JsonResponse
    {
        $request->validate(['alt' => ['nullable', 'string', 'max:200']]);
        $media->update(['alt' => $sanitizer->plain($request->input('alt'), 200) ?: null]);

        return $request->expectsJson() ? response()->json($this->payload($media)) : back()->with('status', __('Description saved.'));
    }

    public function destroy(Media $media, MediaStore $store, SiteSettings $settings): RedirectResponse
    {
        if ($usage = $this->usage($media, $settings)) {
            return back()->withErrors(['media' => __('This image is still used (:where). Replace it there first.', ['where' => $usage])]);
        }

        $name = $media->original_name;
        $store->delete($media);
        ActivityLog::record('media.deleted', __('Image deleted: :name', ['name' => $name]));

        return back()->with('status', __('Image deleted.'));
    }

    /** Where is the image used? (readable text, or null when unused) */
    private function usage(Media $media, SiteSettings $settings): ?string
    {
        foreach (['logo_id' => __('logo'), 'favicon_id' => __('browser tab icon'), 'og_image_id' => __('sharing image')] as $key => $label) {
            if ((int) $settings->get($key) === $media->id) {
                return __('Site information: :what', ['what' => $label]);
            }
        }
        foreach (app(ThemeManager::class)->all() as $theme) {
            $values = (array) $settings->get('theme_options.'.$theme->slug, []);
            foreach ($theme->options() as $option) {
                if ($option['type'] === 'media' && (int) ($values[$option['name']] ?? 0) === $media->id) {
                    return __('options of the theme ":name"', ['name' => $theme->name()]);
                }
            }
        }
        if ($page = Page::where('og_image_id', $media->id)->first()) {
            return __('sharing image of the page ":title"', ['title' => $page->title]);
        }

        $pattern = '/"(image|poster)":'.$media->id.'[,}]/';
        foreach (Block::with('page')->get() as $block) {
            if (preg_match($pattern, json_encode($block->data))) {
                return __('page ":title"', ['title' => $block->page->title]);
            }
        }

        return null;
    }

    private function payload(Media $m): array
    {
        return [
            'id' => $m->id,
            'url' => $m->url(),
            'name' => $m->original_name,
            'alt' => $m->alt,
            'width' => $m->width,
            'height' => $m->height,
            'size' => $m->humanSize(),
        ];
    }
}
