@extends('admin.layout', ['title' => __('Languages')])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Languages') }}</h1>
        <p class="mt-1 text-stone-500">{{ __('Publish your site in several languages, and choose the language of the administration.') }}</p>
    </div>

    @error('language')
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-red-900" role="alert">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('admin.languages.save') }}" class="ck-card p-5 sm:p-6 mb-8 space-y-5">
        @csrf @method('PUT')
        <h2 class="text-lg font-bold flex items-center gap-2"><x-icon name="translate" class="text-[22px] text-emerald-700" />{{ __('Languages of the site') }}</h2>

        <div class="divide-y divide-stone-100 border border-stone-200 rounded-2xl">
            @foreach ($packs as $code => $pack)
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3">
                    <label class="flex items-center gap-3 flex-1 min-w-0 cursor-pointer">
                        <input type="checkbox" name="site_locales[]" value="{{ $code }}" @checked(in_array($code, $siteLocales, true)) @disabled($code === $defaultLocale) class="w-5 h-5 accent-emerald-700">
                        @if ($code === $defaultLocale)<input type="hidden" name="site_locales[]" value="{{ $code }}">@endif
                        <span class="min-w-0">
                            <span class="block font-semibold">{{ $pack->nativeName() }} <span class="font-normal text-stone-500">· {{ $pack->name() }} · <code>{{ $code }}</code></span></span>
                            <span class="block text-xs text-stone-500">
                                {{ $code === 'en' ? __('Source language of MyCMS') : trans_choice(':count translated string|:count translated strings', $pack->stringsCount()) }}
                                · {{ trans_choice(':count page|:count pages', $pageCounts[$code] ?? 0) }}
                                · {{ $pack->bundled ? __('built-in') : __('installed') }}{{ $pack->version() && $code !== 'en' ? ' · v'.$pack->version() : '' }}
                                @if (in_array($code, $siteLocales, true)) · <span class="font-semibold text-emerald-700">{{ $code === $defaultLocale ? __('default language, at the root of the site') : __('pages under :prefix', ['prefix' => '/'.$pack->urlPrefix()]) }}</span>@endif
                            </span>
                        </span>
                    </label>
                    @if ($canInstall && ! $pack->bundled)
                        <div class="flex gap-1 sm:shrink-0">
                            @if ($manager->sourceUrl($pack))
                                <button type="submit" form="update-{{ $code }}" class="ck-icon-btn" title="{{ __('Update') }}" aria-label="{{ __('Update') }} {{ $pack->name() }}"><x-icon name="sync" class="text-[20px]" /></button>
                            @endif
                            <button type="submit" form="delete-{{ $code }}" class="ck-icon-btn hover:!text-red-700 hover:!bg-red-50" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }} {{ $pack->name() }}"><x-icon name="delete" class="text-[20px]" /></button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="default_locale" class="ck-label">{{ __('Default language of the site') }}</label>
                <select id="default_locale" name="default_locale" class="ck-input">
                    @foreach ($packs as $code => $pack)
                        <option value="{{ $code }}" @selected($code === $defaultLocale)>{{ $pack->nativeName() }}</option>
                    @endforeach
                </select>
                <p class="ck-help">{{ __('Its pages are served at the root of the site (/about); the other languages under a prefix (/fr/about).') }}</p>
                @error('default_locale')<p class="ck-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="admin_locale" class="ck-label">{{ __('Default language of the administration') }}</label>
                <select id="admin_locale" name="admin_locale" class="ck-input">
                    @foreach ($packs as $code => $pack)
                        <option value="{{ $code }}" @selected($code === $adminLocale)>{{ $pack->nativeName() }}</option>
                    @endforeach
                </select>
                <p class="ck-help">{{ __('Each administrator can choose their own language in "My account".') }}</p>
            </div>
        </div>

        <div class="rounded-xl bg-sky-50 border border-sky-200 px-4 py-3 text-sm text-sky-900">
            <p class="font-semibold">{{ __('How to translate the site?') }}</p>
            <p class="mt-1">{{ __('1. Tick the language and save. 2. In "Pages", open each page and click "Translate into…": a copy is created, translate its texts. 3. In "Site information", choose the language at the top to translate the name, the footer, the button…') }}</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="ck-btn-primary"><x-icon name="save" class="text-[18px]" />{{ __('Save') }}</button>
        </div>
    </form>

    @foreach ($packs as $code => $pack)
        @if ($canInstall && ! $pack->bundled)
            <form id="update-{{ $code }}" method="POST" action="{{ route('admin.languages.update', $code) }}" class="hidden">@csrf</form>
            <form id="delete-{{ $code }}" method="POST" action="{{ route('admin.languages.destroy', $code) }}" class="hidden" @submit="if (!confirm(@js(__('Delete the language ":name"?', ['name' => $pack->nativeName()])))) $event.preventDefault()">@csrf @method('DELETE')</form>
        @endif
    @endforeach

    @if ($canInstall)
        <div class="max-w-3xl space-y-4">
            @include('admin.partials.package-install', [
                'action' => route('admin.languages.install'),
                'title' => __('Install a language'),
                'help' => __('A language pack only contains texts (JSON files): it cannot run any code.'),
                'placeholder' => 'https://github.com/someone/mycms-lang-de',
            ])
            <p class="text-sm text-stone-500">{{ __('Want to translate MyCMS into your language? Read the guide docs/LANGUAGES.md delivered with MyCMS.') }}</p>
        </div>
    @endif
@endsection
