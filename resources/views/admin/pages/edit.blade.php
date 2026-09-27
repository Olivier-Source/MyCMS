@extends('admin.layout', ['title' => $page->title])

@section('content')
    <a href="{{ route('admin.pages.index', ['lang' => $page->locale]) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-stone-900 mb-4"><x-icon name="arrow_back" class="text-[18px]" />{{ __('Pages') }}</a>

    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 mb-6">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-bold">{{ $page->title }}</h1>
                @if ($page->is_published)
                    <span class="ck-badge bg-emerald-50 text-emerald-800"><span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>{{ __('Online') }}</span>
                @else
                    <span class="ck-badge bg-amber-50 text-amber-800"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ __('Disabled') }}</span>
                @endif
                @if ($languages->isMultilingual())
                    <span class="ck-badge bg-stone-100 text-stone-700"><x-icon name="translate" class="text-[14px]" />{{ $languages->find($page->locale)?->nativeName() }}</span>
                @endif
            </div>
            <p class="mt-1 text-stone-500">{{ __('Your page is made of sections, shown from top to bottom. Click on a section to edit it, drag it to move it.') }}</p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <a href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener" class="ck-btn-secondary"><x-icon name="visibility" class="text-[18px]" />{{ __('Preview') }}</a>
            <a href="{{ route('admin.pages.settings', $page) }}" class="ck-btn-secondary"><x-icon name="settings" class="text-[18px]" />{{ __('Settings') }}</a>
            @unless ($page->is_home)
                <form method="POST" action="{{ route('admin.pages.publish', $page) }}">
                    @csrf
                    <button type="submit" class="{{ $page->is_published ? 'ck-btn-secondary' : 'ck-btn-primary' }}">
                        <x-icon :name="$page->is_published ? 'visibility_off' : 'public'" class="text-[18px]" />{{ $page->is_published ? __('Disable') : __('Publish the page') }}
                    </button>
                </form>
            @endunless
        </div>
    </div>

    @if ($languages->isMultilingual())
        <div class="ck-card p-4 mb-6 flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="text-sm font-semibold flex items-center gap-2 shrink-0"><x-icon name="translate" class="text-[20px] text-emerald-700" />{{ __('Translations') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($languages->siteLocales() as $code)
                    @continue($code === $page->locale)
                    @if ($translation = $translations[$code] ?? null)
                        <a href="{{ route('admin.pages.edit', $translation) }}" class="ck-btn-secondary py-1.5 text-xs">
                            <x-icon name="check_circle" class="text-[16px] text-emerald-600" />{{ $languages->find($code)->nativeName() }}
                        </a>
                    @else
                        <form method="POST" action="{{ route('admin.pages.translate', $page) }}">
                            @csrf
                            <input type="hidden" name="locale" value="{{ $code }}">
                            <button type="submit" class="ck-btn-secondary py-1.5 text-xs border-dashed"><x-icon name="add" class="text-[16px]" />{{ __('Translate into :language', ['language' => $languages->find($code)->nativeName()]) }}</button>
                        </form>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($blocks->isEmpty())
        <div class="ck-card p-10 text-center">
            <x-icon name="note_add" class="text-[48px] text-stone-300" />
            <p class="mt-3 font-semibold">{{ __('This page is empty.') }}</p>
            <p class="text-stone-500">{{ __('Add a first section to start.') }}</p>
            <a href="{{ route('admin.blocks.create', $page) }}" class="ck-btn-primary mt-5"><x-icon name="add" class="text-[20px]" />{{ __('Add a section') }}</a>
        </div>
    @else
        <div class="space-y-3" x-data="reorder('{{ route('admin.blocks.reorder', $page) }}')" x-sort="save()">
            @foreach ($blocks as $block)
                @php $def = $registry[$block->type] ?? ['label' => $block->type, 'icon' => 'help']; @endphp
                <div data-id="{{ $block->id }}" x-sort:item="{{ $block->id }}" class="group">
                    <div class="ck-card flex items-center gap-2 sm:gap-3 p-3 sm:p-4 {{ $block->is_visible ? '' : 'opacity-60 border-dashed' }}">
                        <button type="button" x-sort:handle class="ck-icon-btn cursor-grab active:cursor-grabbing" aria-label="{{ __('Move the section') }}"><x-icon name="drag_indicator" class="text-[22px]" /></button>
                        <a href="{{ isset($registry[$block->type]) ? route('admin.blocks.edit', $block) : '#' }}" class="flex items-center gap-3 flex-1 min-w-0">
                            <span class="w-11 h-11 shrink-0 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center"><x-icon :name="$def['icon']" class="text-[22px]" /></span>
                            <span class="min-w-0">
                                <span class="block font-semibold truncate">{{ $block->summary() ?: $def['label'] }}</span>
                                <span class="block text-sm text-stone-500 truncate">{{ $def['label'] }}{{ $block->is_visible ? '' : ' · '.__('hidden') }}{{ isset($registry[$block->type]) ? '' : ' · '.__('not available with the current theme') }}</span>
                            </span>
                        </a>
                        <div class="flex items-center gap-0.5 shrink-0">
                            @isset($registry[$block->type])
                                <a href="{{ route('admin.blocks.edit', $block) }}" class="ck-btn-secondary py-2 hidden sm:inline-flex"><x-icon name="edit" class="text-[18px]" />{{ __('Edit') }}</a>
                            @endisset
                            <form method="POST" action="{{ route('admin.blocks.toggle', $block) }}">
                                @csrf
                                <button type="submit" class="ck-icon-btn" title="{{ $block->is_visible ? __('Hide') : __('Show') }}" aria-label="{{ $block->is_visible ? __('Hide the section') : __('Show the section') }}">
                                    <x-icon :name="$block->is_visible ? 'visibility' : 'visibility_off'" class="text-[20px]" />
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.blocks.duplicate', $block) }}" class="hidden sm:block">
                                @csrf
                                <button type="submit" class="ck-icon-btn" title="{{ __('Duplicate') }}" aria-label="{{ __('Duplicate the section') }}"><x-icon name="content_copy" class="text-[20px]" /></button>
                            </form>
                            <form method="POST" action="{{ route('admin.blocks.destroy', $block) }}" @submit="if (!confirm(@js(__('Delete this section?').'\n\n'.__('Tip: you can also hide it with the eye.')))) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <button type="submit" class="ck-icon-btn hover:!text-red-700 hover:!bg-red-50" title="{{ __('Delete') }}" aria-label="{{ __('Delete the section') }}"><x-icon name="delete" class="text-[20px]" /></button>
                            </form>
                        </div>
                    </div>
                    <div class="flex justify-center h-3 group-hover:h-9 overflow-hidden transition-all">
                        <a href="{{ route('admin.blocks.create', ['page' => $page, 'after' => $block->id]) }}" class="self-center inline-flex items-center gap-1 rounded-full bg-white border border-stone-200 px-3 py-1 text-xs font-semibold text-stone-600 hover:text-emerald-800 hover:border-emerald-300 opacity-0 group-hover:opacity-100 transition">
                            <x-icon name="add" class="text-[16px]" />{{ __('Insert a section here') }}
                        </a>
                    </div>
                </div>
            @endforeach
            <p x-show="saved" x-transition class="text-sm text-emerald-700" x-cloak>{{ __('Order saved') }} ✓</p>
        </div>

        <div class="mt-6 flex justify-center">
            <a href="{{ route('admin.blocks.create', $page) }}" class="ck-btn-primary"><x-icon name="add" class="text-[20px]" />{{ __('Add a section at the end') }}</a>
        </div>
    @endif
@endsection
