@extends('admin.layout', ['title' => __('Pages')])

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Pages') }}</h1>
            <p class="mt-1 text-stone-500">{{ __('Drag the pages to change the order of the menu. A disabled page is no longer visible on the site, but stays here.') }}</p>
        </div>
        <a href="{{ route('admin.pages.create', ['lang' => $locale]) }}" class="ck-btn-primary shrink-0"><x-icon name="add" class="text-[20px]" />{{ __('New page') }}</a>
    </div>

    @if ($languages->isMultilingual())
        <nav class="mb-4 flex flex-wrap gap-2" aria-label="{{ __('Languages') }}">
            @foreach ($languages->siteLocales() as $code)
                <a href="{{ route('admin.pages.index', ['lang' => $code]) }}"
                   class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold border transition {{ $code === $locale ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-stone-700 border-stone-200 hover:border-stone-400' }}">
                    {{ $languages->find($code)->nativeName() }}
                    <span class="ck-badge {{ $code === $locale ? 'bg-white/20 text-white' : 'bg-stone-100 text-stone-600' }}">{{ $counts[$code] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($pages->isEmpty())
        <div class="ck-card p-10 text-center">
            <x-icon name="translate" class="text-[48px] text-stone-300" />
            <p class="mt-3 font-semibold">{{ __('No page in this language yet.') }}</p>
            <p class="text-stone-500">{{ __('Open a page in another language and use "Translate", or create a new page.') }}</p>
        </div>
    @else
        <div class="ck-card divide-y divide-stone-100" x-data="reorder('{{ route('admin.pages.reorder') }}')" x-sort="save()">
            @foreach ($pages as $page)
                <div class="flex flex-col md:flex-row md:items-center gap-3 px-4 sm:px-5 py-4 bg-white first:rounded-t-2xl last:rounded-b-2xl" data-id="{{ $page->id }}" x-sort:item="{{ $page->id }}">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <button type="button" x-sort:handle class="ck-icon-btn cursor-grab active:cursor-grabbing" aria-label="{{ __('Move') }}"><x-icon name="drag_indicator" class="text-[22px]" /></button>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="font-bold hover:text-emerald-800 truncate">{{ $page->title }}</a>
                                @if ($page->is_home)
                                    <span class="ck-badge bg-stone-100 text-stone-700"><x-icon name="home" class="text-[14px]" />{{ __('Home') }}</span>
                                @endif
                                @if ($page->is_published)
                                    <span class="ck-badge bg-emerald-50 text-emerald-800"><span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>{{ __('Online') }}</span>
                                @else
                                    <span class="ck-badge bg-amber-50 text-amber-800"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ __('Disabled') }}</span>
                                @endif
                                @if ($page->show_in_nav)<span class="ck-badge bg-sky-50 text-sky-800">{{ __('Menu') }}</span>@endif
                                @if ($page->show_in_footer)<span class="ck-badge bg-violet-50 text-violet-800">{{ __('Footer') }}</span>@endif
                            </div>
                            <p class="text-sm text-stone-500 truncate">{{ $page->path() }} · {{ trans_choice(':count section|:count sections', $page->blocks_count) }} · {{ __('edited :time', ['time' => $page->updated_at->diffForHumans()]) }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 md:shrink-0 pl-12 md:pl-0">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="ck-btn-secondary py-2"><x-icon name="edit" class="text-[18px]" />{{ __('Edit') }}</a>
                        <a href="{{ route('admin.pages.settings', $page) }}" class="ck-icon-btn" title="{{ __('Settings') }}" aria-label="{{ __('Settings of :title', ['title' => $page->title]) }}"><x-icon name="settings" class="text-[20px]" /></a>
                        <a href="{{ $page->is_published ? $page->url() : route('admin.pages.preview', $page) }}" target="_blank" rel="noopener" class="ck-icon-btn" title="{{ __('View') }}" aria-label="{{ __('View :title', ['title' => $page->title]) }}"><x-icon name="visibility" class="text-[20px]" /></a>
                        @unless ($page->is_home)
                            <form method="POST" action="{{ route('admin.pages.publish', $page) }}">
                                @csrf
                                <button type="submit" class="ck-icon-btn" title="{{ $page->is_published ? __('Disable') : __('Publish') }}" aria-label="{{ $page->is_published ? __('Disable') : __('Publish') }} {{ $page->title }}">
                                    <x-icon :name="$page->is_published ? 'visibility_off' : 'public'" class="text-[20px]" />
                                </button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('admin.pages.duplicate', $page) }}">
                            @csrf
                            <button type="submit" class="ck-icon-btn" title="{{ __('Duplicate') }}" aria-label="{{ __('Duplicate :title', ['title' => $page->title]) }}"><x-icon name="content_copy" class="text-[20px]" /></button>
                        </form>
                        @unless ($page->is_home && $page->locale === $languages->defaultLocale())
                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" @submit="if (!confirm(@js(__('Permanently delete the page ":title" and all its content?', ['title' => $page->title]).'\n\n'.__('Tip: you can disable it instead.')))) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <button type="submit" class="ck-icon-btn hover:!text-red-700 hover:!bg-red-50" title="{{ __('Delete') }}" aria-label="{{ __('Delete :title', ['title' => $page->title]) }}"><x-icon name="delete" class="text-[20px]" /></button>
                            </form>
                        @endunless
                    </div>
                </div>
            @endforeach
            <p x-show="saved" x-transition class="px-5 py-2 text-sm text-emerald-700" x-cloak>{{ __('Order saved') }} ✓</p>
        </div>
    @endif
@endsection
