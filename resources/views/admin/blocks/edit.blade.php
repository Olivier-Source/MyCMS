@extends('admin.layout', ['title' => $definition['label'], 'inlineErrors' => true])

@section('content')
    @php
        $anchor = \Illuminate\Support\Str::slug($data['anchor'] ?? '') ?: 'section-'.$block->id;
        $previewUrl = route('admin.pages.preview', $page).'#'.$anchor;
    @endphp

    <a href="{{ route('admin.pages.edit', $page) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-stone-900 mb-4"><x-icon name="arrow_back" class="text-[18px]" />{{ $page->title }}</a>

    <form method="POST" action="{{ route('admin.blocks.update', $block) }}"
          x-data="blockEditor(@js($data), @js($errors->getMessages()), @js($mediaMap))" @submit="submit()">
        @csrf @method('PUT')
        <input type="hidden" name="data" :value="serialized()">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-12 h-12 shrink-0 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center"><x-icon :name="$definition['icon']" class="text-[26px]" /></span>
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-bold">{{ $definition['label'] }}</h1>
                    <p class="text-sm text-stone-500">{{ $definition['description'] }}</p>
                </div>
            </div>
            <div class="flex gap-2 shrink-0">
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="ck-btn-secondary"><x-icon name="visibility" class="text-[18px]" />{{ __('View') }}</a>
                <button type="submit" class="ck-btn-primary"><x-icon name="save" class="text-[18px]" />{{ __('Save') }}</button>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 flex items-start gap-3 rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-red-900" role="alert">
                <x-icon name="error" class="text-[22px] text-red-600 mt-0.5" />
                <p class="text-[15px]">{{ __('Some fields need to be corrected (shown in red below). Nothing has been saved.') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 items-start">
            <div class="xl:col-span-3 space-y-6">
                <div class="ck-card p-5 sm:p-6 space-y-5">
                    @foreach ($definition['fields'] as $field)
                        @include('admin.fields.field', ['field' => $field, 'model' => 'data', 'path' => "''", 'depth' => 0])
                    @endforeach
                </div>

                <div class="sticky bottom-4 z-20 flex items-center justify-between gap-3 rounded-2xl bg-stone-900 text-white px-4 py-3 shadow-xl" x-show="dirty" x-transition x-cloak>
                    <span class="text-sm">{{ __('Unsaved changes') }}</span>
                    <button type="submit" class="ck-btn bg-white text-stone-900 hover:bg-stone-100"><x-icon name="save" class="text-[18px]" />{{ __('Save') }}</button>
                </div>
            </div>

            <aside class="xl:col-span-2 space-y-4 xl:sticky xl:top-6">
                <div class="ck-card overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-stone-200">
                        <p class="font-semibold text-sm flex items-center gap-2"><x-icon name="visibility" class="text-[18px] text-emerald-700" />{{ __('Page preview') }}</p>
                        <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="text-xs font-semibold text-emerald-800 hover:underline">{{ __('Full screen') }}</a>
                    </div>
                    <div class="relative h-[28rem] overflow-hidden bg-stone-100">
                        {{-- Scrolls to the section inside the preview only (an anchor in the URL would also scroll this page) --}}
                        <iframe src="{{ route('admin.pages.preview', $page) }}" title="{{ __('Preview') }}" loading="lazy"
                                @load="const el = $el.contentDocument?.getElementById('{{ $anchor }}'); if (el) $el.contentWindow.scrollTo(0, el.getBoundingClientRect().top + $el.contentWindow.scrollY - 80)"
                                class="absolute top-0 left-0 w-[250%] h-[250%] origin-top-left scale-[0.4] border-0"></iframe>
                    </div>
                    <p class="px-4 py-2 text-xs text-stone-500">{{ __('The preview shows the saved version.') }}</p>
                </div>

                <details class="ck-card p-4 group">
                    <summary class="font-semibold text-sm flex items-center justify-between cursor-pointer list-none">
                        <span class="flex items-center gap-2"><x-icon name="info" class="text-[18px] text-emerald-700" />{{ __('Insert a site information') }}</span>
                        <x-icon name="expand_more" class="text-[20px] text-stone-400" />
                    </summary>
                    <p class="text-sm text-stone-500 mt-3">{{ __('Type these words between braces in any text: they are replaced automatically, and updated when the information changes.') }}</p>
                    <ul class="mt-3 space-y-1.5 text-sm max-h-72 overflow-y-auto">
                        @foreach ($tags as $tag => $label)
                            <li class="flex items-center justify-between gap-2">
                                <code class="rounded bg-stone-100 px-1.5 py-0.5 text-emerald-800">{{ '{'.$tag.'}' }}</code>
                                <span class="text-stone-500 text-right truncate">{{ $label }}</span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            </aside>
        </div>

        <datalist id="internal-links">
            @foreach ($internalLinks as $link)
                <option value="{{ $link['url'] }}">{{ $link['label'] }}</option>
            @endforeach
            <option value="{phone}">{{ __('Call the phone number') }}</option>
            <option value="{email}">{{ __('Write an e-mail') }}</option>
            <option value="{button}">{{ __('Link of the main button') }}</option>
        </datalist>
    </form>
@endsection
