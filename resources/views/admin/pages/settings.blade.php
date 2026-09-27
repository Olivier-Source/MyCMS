@extends('admin.layout', ['title' => __('Settings').' · '.$page->title])

@section('content')
    <a href="{{ route('admin.pages.edit', $page) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-stone-900 mb-4"><x-icon name="arrow_back" class="text-[18px]" />{{ $page->title }}</a>
    <h1 class="text-2xl sm:text-3xl font-bold mb-6">{{ __('Page settings') }}</h1>

    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-6">
        @csrf @method('PUT')

        <section class="ck-card p-5 sm:p-6 space-y-5">
            <h2 class="font-bold text-lg">{{ __('General') }}</h2>
            <div>
                <label for="title" class="ck-label">{{ __('Page title') }}</label>
                <input id="title" name="title" value="{{ old('title', $page->title) }}" required maxlength="120" class="ck-input">
            </div>
            @unless ($page->is_home)
                <div>
                    <label for="slug" class="ck-label">{{ __('Page address') }}</label>
                    <div class="flex items-center rounded-xl border border-stone-300 bg-stone-50 focus-within:ring-4 focus-within:ring-emerald-600/15 focus-within:border-emerald-600">
                        <span class="pl-3.5 text-stone-500 text-[15px] whitespace-nowrap">{{ rtrim(site_url(app(\App\Cms\Languages\LanguageManager::class)->pathPrefix($page->locale) ?: '/'), '/') }}/</span>
                        <input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" required maxlength="80" class="flex-1 min-w-0 bg-transparent px-1 py-2.5 text-[15px] focus:outline-none">
                    </div>
                    <p class="ck-help">{{ __('Careful: if you change the address, links already shared to this page will no longer work.') }}</p>
                </div>
                <label class="flex items-start justify-between gap-4 rounded-xl bg-stone-50 p-4">
                    <span>
                        <span class="block font-semibold">{{ __('Page online') }}</span>
                        <span class="block text-sm text-stone-500">{{ __('When disabled, visitors no longer see the page (it can still be edited here).') }}</span>
                    </span>
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published)) class="ck-toggle mt-1">
                </label>
            @endunless
        </section>

        <section class="ck-card p-5 sm:p-6 space-y-4">
            <h2 class="font-bold text-lg">{{ __('Menus') }}</h2>
            <label class="flex items-start justify-between gap-4 rounded-xl bg-stone-50 p-4">
                <span><span class="block font-semibold">{{ __('Show in the main menu') }}</span><span class="block text-sm text-stone-500">{{ __('At the top of every page.') }}</span></span>
                <input type="checkbox" name="show_in_nav" value="1" @checked(old('show_in_nav', $page->show_in_nav)) class="ck-toggle mt-1">
            </label>
            <label class="flex items-start justify-between gap-4 rounded-xl bg-stone-50 p-4">
                <span><span class="block font-semibold">{{ __('Show in the footer') }}</span><span class="block text-sm text-stone-500">{{ __('"Information" column: legal notice, privacy policy…') }}</span></span>
                <input type="checkbox" name="show_in_footer" value="1" @checked(old('show_in_footer', $page->show_in_footer)) class="ck-toggle mt-1">
            </label>
            <div>
                <label for="nav_label" class="ck-label">{{ __('Name in the menus') }} <span class="font-normal text-stone-500">({{ __('optional') }})</span></label>
                <input id="nav_label" name="nav_label" value="{{ old('nav_label', $page->nav_label) }}" maxlength="40" class="ck-input" placeholder="{{ $page->title }}">
                <p class="ck-help">{{ __('A shorter name than the title, if needed.') }}</p>
            </div>
        </section>

        <section class="ck-card p-5 sm:p-6 space-y-5" x-data="{ image: {{ old('og_image_id', $page->og_image_id) ?: 'null' }} }" x-init="$store.media.register(@js($ogImage ? [$ogImage->id => ['url' => $ogImage->url(), 'name' => $ogImage->original_name]] : []))">
            <div>
                <h2 class="font-bold text-lg">{{ __('Search engines & sharing') }}</h2>
                <p class="ck-help mt-0">{{ __('Optional: otherwise, the "Search engines" settings of the site information are used.') }}</p>
            </div>
            <div>
                <label for="meta_title" class="ck-label">{{ __('Title for search engines') }}</label>
                <input id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" maxlength="70" class="ck-input" placeholder="{{ $page->title }}">
            </div>
            <div>
                <label for="meta_description" class="ck-label">{{ __('Description for search engines') }}</label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="300" class="ck-input">{{ old('meta_description', $page->meta_description) }}</textarea>
                <p class="ck-help">{{ __('One or two sentences summing up the page (ideally 160 characters).') }}</p>
            </div>
            <div x-data="imageField(() => image, v => image = v)">
                <span class="ck-label">{{ __('Sharing image') }}</span>
                <input type="hidden" name="og_image_id" :value="image || ''">
                @include('admin.fields.image-preview')
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.pages.edit', $page) }}" class="ck-btn-ghost">{{ __('Cancel') }}</a>
            <button type="submit" class="ck-btn-primary">{{ __('Save') }}</button>
        </div>
    </form>
@endsection
