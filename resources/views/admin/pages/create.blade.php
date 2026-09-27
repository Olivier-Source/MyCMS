@extends('admin.layout', ['title' => __('New page')])

@section('content')
    <a href="{{ route('admin.pages.index', ['lang' => $locale]) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-stone-900 mb-4"><x-icon name="arrow_back" class="text-[18px]" />{{ __('Pages') }}</a>
    <h1 class="text-2xl sm:text-3xl font-bold mb-6">{{ __('Create a page') }}</h1>

    <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-6" x-data="{ template: '{{ old('template', 'presentation') }}' }">
        @csrf
        <div class="ck-card p-5 sm:p-6 space-y-5">
            <div>
                <label for="title" class="ck-label">{{ __('Page title') }}</label>
                <input id="title" name="title" value="{{ old('title') }}" required maxlength="120" class="ck-input" placeholder="{{ __('E.g. Our services') }}">
                <p class="ck-help">{{ __('Shown in the menu and in the browser tab.') }}</p>
            </div>
            <div>
                <label for="slug" class="ck-label">{{ __('Page address') }} <span class="font-normal text-stone-500">({{ __('optional') }})</span></label>
                <div class="flex items-center rounded-xl border border-stone-300 bg-stone-50 focus-within:ring-4 focus-within:ring-emerald-600/15 focus-within:border-emerald-600">
                    <span class="pl-3.5 text-stone-500 text-[15px] whitespace-nowrap">{{ rtrim(site_url($languages->pathPrefix($locale) ?: '/'), '/') }}/</span>
                    <input id="slug" name="slug" value="{{ old('slug') }}" maxlength="80" class="flex-1 min-w-0 bg-transparent px-1 py-2.5 text-[15px] focus:outline-none" placeholder="{{ __('computed from the title') }}">
                </div>
            </div>
            @if ($languages->isMultilingual())
                <div>
                    <label for="locale" class="ck-label">{{ __('Language of the page') }}</label>
                    <select id="locale" name="locale" class="ck-input">
                        @foreach ($languages->siteLocales() as $code)
                            <option value="{{ $code }}" @selected(old('locale', $locale) === $code)>{{ $languages->find($code)->nativeName() }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="locale" value="{{ $locale }}">
            @endif
        </div>

        <fieldset class="ck-card p-5 sm:p-6">
            <legend class="sr-only">{{ __('Starting point') }}</legend>
            <p class="font-bold mb-1">{{ __('Starting point') }}</p>
            <p class="ck-help mt-0 mb-4">{{ __('You can add, remove and reorder the sections afterwards.') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($templates as $key => $tpl)
                    <label class="flex gap-3 rounded-xl border p-4 cursor-pointer transition" :class="template === '{{ $key }}' ? 'border-emerald-600 bg-emerald-50 ring-4 ring-emerald-600/10' : 'border-stone-200 hover:border-stone-300'">
                        <input type="radio" name="template" value="{{ $key }}" x-model="template" class="sr-only">
                        <span class="w-10 h-10 shrink-0 rounded-lg bg-white border border-stone-200 flex items-center justify-center text-emerald-700"><x-icon :name="$tpl['icon']" class="text-[22px]" /></span>
                        <span>
                            <span class="block font-semibold">{{ $tpl['label'] }}</span>
                            <span class="block text-sm text-stone-500">{{ $tpl['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.pages.index', ['lang' => $locale]) }}" class="ck-btn-ghost">{{ __('Cancel') }}</a>
            <button type="submit" class="ck-btn-primary">{{ __('Create the page') }}</button>
        </div>
    </form>
@endsection
