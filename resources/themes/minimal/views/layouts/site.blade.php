{{--
    "Minimal" theme layout. Every variable used here is provided by MyCMS to
    all the templates of a theme ($settings, $theme, $navPages, $ctaUrl…):
    see docs/THEMES.md.
--}}
@php
    use App\Cms\ThemePalette;
    $page = $page ?? null;
    $siteName = $settings->siteName();
    $suffix = $settings->get('seo_suffix') ?: $siteName;
    $metaTitle = $page?->meta_title ?: ($page?->is_home ? null : $page?->title);
    $fullTitle = $metaTitle ? $metaTitle.' — '.$suffix : $suffix;
    $description = $ph->raw($page?->meta_description ?: $settings->get('seo_description'));
    $ogImage = ($page?->og_image_id ? \App\Models\Media::find($page->og_image_id) : null) ?? $r->setting('og_image_id');
    $logo = $r->setting('logo_id');
    $favicon = $r->setting('favicon_id');
    $palette = ThemePalette::sanitize($settings->get('palette') ?: ThemePalette::defaults($theme));
    $current = $page?->path();
    $alternates = $alternates ?? [];
    $centered = theme_option('header_layout', 'centered') === 'centered';
    $menuCase = theme_option('uppercase_menu', true) ? 'uppercase tracking-[0.14em] text-xs' : 'text-sm';
    $nonce = $cspNonce ?? \Illuminate\Support\Facades\Vite::cspNonce();
@endphp
<!DOCTYPE html>
<html lang="{{ $language?->htmlLang() ?? 'en' }}" dir="{{ $language?->direction() ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    @unless ($settings->get('allow_indexing') && ! ($preview ?? false))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <meta name="theme-color" content="{{ $palette['background'] }}">
    @if ($page)
        <link rel="canonical" href="{{ $page->url() }}">
        @foreach ($alternates as $code => $url)
            <link rel="alternate" hreflang="{{ $languages->find($code)?->htmlLang() ?? $code }}" href="{{ $url }}">
        @endforeach
    @endif
    @if ($favicon)<link rel="icon" href="{{ $favicon->url() }}" type="{{ $favicon->mime }}">@endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    @if ($description)<meta property="og:description" content="{{ $description }}">@endif
    @if ($page)<meta property="og:url" content="{{ $page->url() }}">@endif
    <meta property="og:locale" content="{{ $language?->ogLocale() ?? 'en_US' }}">
    @if ($ogImage)<meta property="og:image" content="{{ $ogImage->url() }}">@endif
    <style nonce="{{ $nonce }}">:root{@foreach (ThemePalette::cssVariables($palette) as $token => $rgb)--c-{{ $token }}:{{ $rgb }};@endforeach}</style>
    @foreach ($theme->styles() as $href)<link rel="stylesheet" href="{{ $href }}">@endforeach
    @foreach ($theme->scripts() as $src)<script src="{{ $src }}" defer nonce="{{ $nonce }}"></script>@endforeach
</head>
<body class="bg-surface font-body text-on-surface antialiased">

<a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:px-4 focus:py-2 focus:bg-btn focus:text-on-btn">{{ __('Skip to content') }}</a>

@if ($preview ?? false)
    <div class="bg-on-surface text-surface text-sm text-center px-4 py-2">
        {{ __('Preview from the administration') }}{{ $page && ! $page->is_published ? ' — '.__('this page is not published') : '' }}.
    </div>
@endif

@if ($settings->announcementActive())
    <div class="bg-on-surface text-surface text-xs sm:text-sm text-center px-4 py-2.5">
        @if ($settings->get('announcement_url'))
            <a {!! $r->linkAttrs($settings->get('announcement_url')) !!} class="underline underline-offset-4">{!! $ph->text($settings->get('announcement_text'), false) !!}</a>
        @else
            {!! $ph->text($settings->get('announcement_text'), false) !!}
        @endif
    </div>
@endif

<header class="relative bg-surface border-b border-outline-variant/40">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 {{ $centered ? 'py-6 sm:py-8' : 'py-5' }}">
        <div class="flex items-center {{ $centered ? 'justify-between xl:justify-center' : 'justify-between' }} gap-4">
            <a href="{{ $homeUrl }}" class="flex {{ $centered ? 'xl:flex-col xl:text-center' : '' }} items-center gap-3 min-w-0">
                @if ($logo)
                    <img src="{{ $logo->url() }}" alt="" class="h-10 w-auto max-w-[9rem] object-contain">
                @endif
                <span class="min-w-0">
                    <span class="block font-headline font-bold text-xl sm:text-2xl tracking-tight text-on-surface truncate">{{ $siteName }}</span>
                    @if ($settings->get('tagline'))
                        <span class="block text-xs text-on-surface-variant truncate">{{ $settings->get('tagline') }}</span>
                    @endif
                </span>
            </a>

            @unless ($centered)
                <nav class="hidden xl:flex items-center gap-8" aria-label="{{ __('Main menu') }}">
                    @foreach ($navPages as $item)
                        <a href="{{ $item->url() }}" @if ($current === $item->path()) aria-current="page" @endif
                           class="{{ $menuCase }} font-semibold transition-colors {{ $current === $item->path() ? 'text-on-surface underline underline-offset-8 decoration-2' : 'text-on-surface-variant hover:text-on-surface' }}">{{ $item->navLabel() }}</a>
                    @endforeach
                </nav>
            @endunless

            <div class="flex items-center gap-2 {{ $centered ? 'xl:absolute xl:right-8 xl:top-1/2 xl:-translate-y-1/2' : '' }}">
                @if (count($alternates) > 1)
                    <details class="relative" data-dropdown>
                        <summary class="h-10 px-2 flex items-center text-xs font-bold uppercase tracking-wider text-on-surface-variant hover:text-on-surface" aria-label="{{ __('Language') }}">{{ $language?->urlPrefix() }}</summary>
                        <ul class="absolute right-0 mt-2 min-w-36 bg-surface-container-lowest border border-outline-variant/50 shadow-lg py-1 z-10">
                            @foreach ($alternates as $code => $url)
                                <li><a href="{{ $url }}" lang="{{ $languages->find($code)?->htmlLang() }}" class="block px-4 py-2 text-sm {{ $code === $contentLocale ? 'font-bold' : 'hover:bg-surface-container-low' }}">{{ $languages->find($code)?->nativeName() }}</a></li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @if ($ctaLabel && $ctaUrl)
                    <a href="{{ $ctaUrl }}" @if ($r->isExternal($ctaUrl)) target="_blank" rel="noopener noreferrer" @endif
                       class="hidden sm:inline-flex items-center px-5 py-2.5 rounded-full bg-btn text-on-btn text-sm font-semibold hover:bg-btn-hover transition-colors">{{ $ctaLabel }}</a>
                @endif
                @if ($navPages->isNotEmpty())
                    <details id="mobile-menu" class="xl:hidden">
                        <summary class="w-10 h-10 flex items-center justify-center text-on-surface" aria-label="{{ __('Menu') }}"><x-icon name="menu" class="text-[26px]" /></summary>
                        <nav class="absolute left-0 right-0 top-full z-50 bg-surface border-b border-outline-variant/40 shadow-lg" aria-label="{{ __('Mobile menu') }}">
                            <div class="max-w-6xl mx-auto px-5 py-4 flex flex-col">
                                @foreach ($navPages as $item)
                                    <a href="{{ $item->url() }}" class="py-3 border-b border-outline-variant/30 {{ $menuCase }} font-semibold {{ $current === $item->path() ? 'text-on-surface' : 'text-on-surface-variant' }}">{{ $item->navLabel() }}</a>
                                @endforeach
                                @if ($ctaLabel && $ctaUrl)
                                    <a href="{{ $ctaUrl }}" class="mt-4 text-center px-5 py-3 rounded-full bg-btn text-on-btn text-sm font-semibold">{{ $ctaLabel }}</a>
                                @endif
                            </div>
                        </nav>
                    </details>
                @endif
            </div>
        </div>

        @if ($centered && $navPages->isNotEmpty())
            <nav class="hidden xl:flex justify-center gap-10 mt-6" aria-label="{{ __('Main menu') }}">
                @foreach ($navPages as $item)
                    <a href="{{ $item->url() }}" @if ($current === $item->path()) aria-current="page" @endif
                       class="{{ $menuCase }} font-semibold transition-colors {{ $current === $item->path() ? 'text-on-surface underline underline-offset-8 decoration-2' : 'text-on-surface-variant hover:text-on-surface' }}">{{ $item->navLabel() }}</a>
                @endforeach
            </nav>
        @endif
    </div>
</header>

<main id="content">
    @yield('content')
</main>

@include('theme::partials.footer')

</body>
</html>
