<?php

namespace App\Http\Controllers\Admin;

use App\Cms\BlockRegistry;
use App\Cms\ContentSanitizer;
use App\Cms\Placeholders;
use App\Cms\SiteSettings;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Block;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BlockController extends Controller
{
    public function create(Request $request, Page $page): View
    {
        return view('admin.blocks.create', [
            'page' => $page,
            'registry' => BlockRegistry::all(),
            'after' => $request->integer('after') ?: null,
        ]);
    }

    public function store(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(BlockRegistry::all()))],
            'after' => ['nullable', 'integer'],
        ]);

        $block = DB::transaction(function () use ($page, $data) {
            $after = ($data['after'] ?? null) ? $page->blocks()->whereKey($data['after'])->first() : null;
            $position = $after ? $after->position + 1 : (int) $page->blocks()->max('position') + 1;
            $page->blocks()->where('position', '>=', $position)->increment('position');

            return $page->blocks()->create([
                'type' => $data['type'],
                'position' => $position,
                'data' => BlockRegistry::defaults($data['type']),
            ]);
        });

        ActivityLog::record('block.created', __('Section ":type" added to :page', ['type' => BlockRegistry::get($block->type)['label'], 'page' => $page->title]));

        return redirect()->route('admin.blocks.edit', $block)->with('status', __('Section added. Fill it in, then save.'));
    }

    public function edit(Block $block, SiteSettings $settings): View
    {
        $definition = $block->definition() ?? abort(404);
        $data = old('data') ? (json_decode(old('data'), true) ?: $block->data) : $block->data;

        // Placeholders shown with the values of the language of the page
        $settings->setContentLocale($block->page->locale);

        return view('admin.blocks.edit', [
            'block' => $block,
            'page' => $block->page,
            'definition' => $definition,
            'data' => BlockRegistry::normalize($block->type, $data),
            'mediaMap' => $this->mediaMap($data ?? []),
            'tags' => Placeholders::catalog(),
            'internalLinks' => Page::inLocale($block->page->locale)->orderBy('position')->get()
                ->map(fn (Page $p) => ['url' => $p->is_home ? '/' : '/'.$p->slug, 'label' => $p->title])->values(),
        ]);
    }

    public function update(Request $request, Block $block, ContentSanitizer $sanitizer): RedirectResponse
    {
        $definition = $block->definition() ?? abort(404);
        $request->validate(['data' => ['required', 'string', 'max:500000']]);

        $input = json_decode($request->input('data'), true);
        if (! is_array($input)) {
            return back()->withErrors(['form' => __('The form could not be read. Reload the page and try again.')]);
        }

        [$clean, $errors] = $sanitizer->clean($definition['fields'], $input);

        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $block->update(['data' => $clean]);
        ActivityLog::record('block.updated', __('Section ":type" changed (:page)', ['type' => $definition['label'], 'page' => $block->page->title]));

        return redirect()->route('admin.blocks.edit', $block)->with('status', __('Changes saved.'));
    }

    public function toggle(Block $block): RedirectResponse
    {
        $block->update(['is_visible' => ! $block->is_visible]);

        return back()->with('status', $block->is_visible ? __('Section shown on the site.') : __('Section hidden (it is kept here).'));
    }

    public function duplicate(Block $block): RedirectResponse
    {
        DB::transaction(function () use ($block) {
            $block->page->blocks()->where('position', '>', $block->position)->increment('position');
            $block->page->blocks()->create($block->only(['type', 'is_visible', 'data']) + ['position' => $block->position + 1]);
        });

        return back()->with('status', __('Section duplicated.'));
    }

    public function destroy(Block $block): RedirectResponse
    {
        $page = $block->page;
        $label = $block->definition()['label'] ?? $block->type;
        $block->delete();
        ActivityLog::record('block.deleted', __('Section ":type" deleted (:page)', ['type' => $label, 'page' => $page->title]));

        return redirect()->route('admin.pages.edit', $page)->with('status', __('Section deleted.'));
    }

    public function reorder(Request $request, Page $page): JsonResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        DB::transaction(function () use ($ids, $page) {
            foreach (array_values($ids) as $i => $id) {
                $page->blocks()->whereKey($id)->update(['position' => $i]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /** Previews of the images already chosen in the block (id → url, name). */
    private function mediaMap(array $data): array
    {
        $ids = [];
        array_walk_recursive($data, function ($value, $key) use (&$ids) {
            if (in_array($key, ['image', 'poster'], true) && is_int($value)) {
                $ids[] = $value;
            }
        });

        return Media::whereIn('id', $ids)->get()
            ->mapWithKeys(fn (Media $m) => [$m->id => ['url' => $m->url(), 'name' => $m->original_name, 'alt' => $m->alt]])
            ->all();
    }
}
