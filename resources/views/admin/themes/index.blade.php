@extends('admin.layout', ['title' => __('Themes')])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Themes') }}</h1>
        <p class="mt-1 text-stone-500">{{ __('A theme changes the whole look of the site. Your pages, texts and pictures are kept when you change theme.') }}</p>
    </div>

    @error('theme')
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-red-900" role="alert">{{ $message }}</div>
    @enderror

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
        @foreach ($themes as $theme)
            @php $isActive = $theme->slug === $active->slug; $source = $manager->sourceUrl($theme); @endphp
            <article class="ck-card overflow-hidden flex flex-col {{ $isActive ? 'ring-4 ring-emerald-600/20 border-emerald-500' : '' }}">
                <div class="aspect-[16/10] bg-stone-100 border-b border-stone-200 flex items-center justify-center overflow-hidden">
                    @if ($theme->screenshot())
                        <img src="{{ $theme->screenshot() }}" alt="" class="w-full h-full object-cover object-top" loading="lazy">
                    @else
                        <x-icon name="web" class="text-[56px] text-stone-300" />
                    @endif
                </div>
                <div class="p-5 flex-1 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2 class="font-bold">{{ __($theme->name()) }}</h2>
                            <p class="text-xs text-stone-500">{{ __('Version :version', ['version' => $theme->version()]) }}{{ $theme->author() ? ' · '.$theme->author() : '' }}</p>
                        </div>
                        @if ($isActive)
                            <span class="ck-badge bg-emerald-600 text-white shrink-0"><x-icon name="check" class="text-[14px]" />{{ __('Active') }}</span>
                        @elseif ($theme->bundled)
                            <span class="ck-badge bg-stone-100 text-stone-600 shrink-0">{{ __('Built-in') }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-stone-600 flex-1">{{ __($theme->description()) }}</p>
                    @if ($theme->parent())
                        <p class="text-xs text-stone-500">{{ __('Based on the theme ":name".', ['name' => $manager->find($theme->parent())?->name() ?? $theme->parent()]) }}</p>
                    @endif
                    @if ($theme->homepage())
                        <a href="{{ $theme->homepage() }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-emerald-800 hover:underline">{{ __('Theme website') }}</a>
                    @endif
                    <div class="flex flex-wrap gap-2 pt-1">
                        @unless ($isActive)
                            <form method="POST" action="{{ route('admin.themes.activate', $theme->slug) }}">
                                @csrf
                                <button type="submit" class="ck-btn-primary py-2">{{ __('Activate') }}</button>
                            </form>
                        @else
                            <a href="{{ route('admin.appearance.edit') }}" class="ck-btn-secondary py-2"><x-icon name="palette" class="text-[18px]" />{{ __('Customise') }}</a>
                        @endunless
                        @if ($canInstall && ! $theme->bundled)
                            @if ($source)
                                <form method="POST" action="{{ route('admin.themes.update', $theme->slug) }}">
                                    @csrf
                                    <button type="submit" class="ck-btn-secondary py-2" title="{{ $source }}"><x-icon name="sync" class="text-[18px]" />{{ __('Update') }}</button>
                                </form>
                            @endif
                            @unless ($isActive)
                                <form method="POST" action="{{ route('admin.themes.destroy', $theme->slug) }}" @submit="if (!confirm(@js(__('Delete the theme ":name"?', ['name' => $theme->name()])))) $event.preventDefault()">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ck-btn-danger py-2"><x-icon name="delete" class="text-[18px]" />{{ __('Delete') }}</button>
                                </form>
                            @endunless
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    @if ($canInstall)
        <div class="max-w-3xl space-y-4">
            @include('admin.partials.package-install', [
                'action' => route('admin.themes.install'),
                'title' => __('Install a theme'),
                'help' => __('A theme contains code: only install themes from people or companies you trust.'),
                'placeholder' => 'https://github.com/someone/mycms-theme-name',
            ])
            <p class="text-sm text-stone-500">{{ __('Want to create your own theme? Read the guide docs/THEMES.md delivered with MyCMS.') }}</p>
        </div>
    @else
        <p class="text-sm text-stone-500">{{ __('Installing themes from the administration is disabled on this server (MYCMS_ALLOW_PACKAGE_INSTALL). Use the command "php artisan mycms:theme".') }}</p>
    @endif
@endsection
