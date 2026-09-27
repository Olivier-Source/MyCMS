@php
    use App\Cms\ThemePalette;
    $page = $page ?? null;
    $siteName = $settings->siteName();
    $suffix = $settings->get('seo_suffix') ?: $siteName;
    $metaTitle = $page?->meta_title ?: ($page?->is_home ? null : $page?->title);
    $fullTitle = $metaTitle ? $metaTitle.' – '.$suffix : ($page?->is_home && $settings->get('tagline') ? $siteName.' – '.$settings->get('tagline') : $suffix);
    $description = $ph->raw($page?->meta_description ?: $settings->get('seo_description'));
    $ogImage = ($page?->og_image_id ? \App\Models\Media::find($page->og_image_id) : null) ?? $r->setting('og_image_id');
    $logo = $r->setting('logo_id');
    $favicon = $r->setting('favicon_id');
    $palette = ThemePalette::sanitize($settings->get('palette') ?: ThemePalette::defaults($theme));
    $themeVars = ThemePalette::cssVariables($palette);
    $current = $page?->path();
    $alternates = $alternates ?? [];
    $htmlClasses = implode(' ', array_filter([
        theme_option('heading_font') === 'sans' ? 'headings-sans' : '',
        theme_option('corners') === 'square' ? 'corners-square' : '',
    ]));
    $sticky = (bool) theme_option('sticky_header', true);
    $nonce = $cspNonce ?? \Illuminate\Support\Facades\Vite::cspNonce();
@endphp
<!DOCTYPE html>
<html lang="{{ $language?->htmlLang() ?? 'en' }}" dir="{{ $language?->direction() ?? 'ltr' }}" class="{{ $htmlClasses }}">
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
    @if ($favicon)
        <link rel="icon" href="{{ $favicon->url() }}" type="{{ $favicon->mime }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    @if ($description)<meta property="og:description" content="{{ $description }}">@endif
    @if ($page)<meta property="og:url" content="{{ $page->url() }}">@endif
    <meta property="og:locale" content="{{ $language?->ogLocale() ?? 'en_US' }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage->url() }}">
    @endif
    <style nonce="{{ $nonce }}">:root{@foreach ($themeVars as $token => $rgb)--c-{{ $token }}:{{ $rgb }};@endforeach}</style>
    @foreach ($theme->styles() as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    @foreach ($theme->scripts() as $src)
        <script src="{{ $src }}" defer nonce="{{ $nonce }}"></script>
    @endforeach
</head>
<body class="bg-surface font-body text-on-surface antialiased">

<a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-lg focus:bg-btn focus:text-on-btn">{{ __('Skip to content') }}</a>

@if ($preview ?? false)
    <div class="bg-stone-900 text-white text-sm text-center px-4 py-2">
        {{ __('Preview from the administration') }}{{ $page && ! $page->is_published ? ' — '.__('this page is not published') : '' }}.
    </div>
@endif

<header class="{{ $sticky ? 'sticky top-0' : 'relative' }} inset-x-0 z-50 bg-surface/85 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.06)]">
    @if ($settings->announcementActive())
        <div class="bg-primary text-on-primary text-xs sm:text-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 py-2 flex items-center justify-center gap-2 text-center">
                <x-icon name="campaign" class="text-[18px] hidden sm:inline-block" />
                @if ($settings->get('announcement_url'))
                    <a {!! $r->linkAttrs($settings->get('announcement_url')) !!} class="underline underline-offset-2 hover:opacity-90">{!! $ph->text($settings->get('announcement_text'), false) !!}</a>
                @else
                    <span>{!! $ph->text($settings->get('announcement_text'), false) !!}</span>
                @endif
            </div>
        </div>
    @endif

    <div class="h-16 sm:h-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 flex items-center justify-between gap-3">
        <a href="{{ $homeUrl }}" class="flex items-center gap-3 min-w-0">
            @if ($logo)
                <img src="{{ $logo->url() }}" alt="" class="h-10 w-auto max-w-[8rem] object-contain shrink-0">
            @else
                <span class="w-10 h-10 shrink-0 rounded-full bg-primary text-on-primary font-headline font-bold text-sm flex items-center justify-center" aria-hidden="true">{{ $settings->get('initials') }}</span>
            @endif
            <span class="flex flex-col min-w-0">
                <span class="font-headline text-base sm:text-lg font-bold text-on-surface leading-snug tracking-tight truncate">{{ $siteName }}</span>
                @if ($settings->get('tagline'))
                    <span class="text-[11px] sm:text-xs text-on-surface-variant tracking-wide truncate">{{ $settings->get('tagline') }}</span>
                @endif
            </span>
        </a>

        <nav class="hidden xl:flex items-center gap-7" aria-label="{{ __('Main menu') }}">
            @foreach ($navPages as $item)
                <a href="{{ $item->url() }}" @if ($current === $item->path()) aria-current="page" @endif
                   class="text-sm transition-colors {{ $current === $item->path() ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface' }}">{{ $item->navLabel() }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            @if ($settings->get('show_contact_in_header') && $settings->get('phone'))
                <a href="{{ $settings->phoneHref() }}" class="hidden lg:flex items-center gap-2 text-sm text-on-surface-variant hover:text-primary transition-colors py-2 px-3 rounded-lg hover:bg-surface-container">
                    <x-icon name="call" class="text-[18px] text-primary" /><span class="font-semibold">{{ $settings->get('phone') }}</span>
                </a>
            @endif

            @if (count($alternates) > 1)
                <details class="relative" data-dropdown>
                    <summary class="flex items-center gap-1 h-10 px-2.5 rounded-lg text-sm font-semibold text-on-surface-variant hover:bg-surface-container" aria-label="{{ __('Language') }}">
                        <x-icon name="language" class="text-[20px]" /><span class="uppercase">{{ $language?->urlPrefix() }}</span>
                    </summary>
                    <ul class="absolute right-0 mt-2 min-w-40 rounded-xl bg-surface-container-lowest shadow-lg py-1.5 z-10">
                        @foreach ($alternates as $code => $url)
                            <li>
                                <a href="{{ $url }}" hreflang="{{ $languages->find($code)?->htmlLang() }}" lang="{{ $languages->find($code)?->htmlLang() }}"
                                   class="block px-4 py-2 text-sm {{ $code === $contentLocale ? 'font-bold text-primary' : 'text-on-surface hover:bg-surface-container' }}">{{ $languages->find($code)?->nativeName() }}</a>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif

            @if ($ctaLabel && $ctaUrl)
                <a href="{{ $ctaUrl }}" @if ($r->isExternal($ctaUrl)) target="_blank" rel="noopener noreferrer" @endif
                   class="inline-flex items-center gap-2 p-2.5 sm:px-4 sm:py-2.5 rounded-lg bg-btn text-on-btn text-sm font-semibold hover:bg-btn-hover shadow-sm transition-all" aria-label="{{ $ctaLabel }}">
                    <x-icon :name="$settings->get('cta_icon') ?: 'arrow_forward'" class="text-[20px] sm:text-[18px]" /><span class="hidden sm:inline">{{ $ctaLabel }}</span>
                </a>
            @endif

            @if ($navPages->isNotEmpty())
                <details id="mobile-menu" class="xl:hidden">
                    <summary class="w-10 h-10 flex items-center justify-center rounded-lg text-on-surface hover:bg-surface-container transition-colors" aria-label="{{ __('Menu') }}">
                        <x-icon name="menu" class="text-[24px]" />
                    </summary>
                    <nav class="absolute left-0 right-0 top-full border-t border-outline-variant/40 bg-surface shadow-lg max-h-[calc(100dvh-5rem)] overflow-y-auto" aria-label="{{ __('Mobile menu') }}">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-col">
                            @foreach ($navPages as $item)
                                <a href="{{ $item->url() }}" @if ($current === $item->path()) aria-current="page" @endif
                                   class="py-3 px-2 rounded-lg {{ $current === $item->path() ? 'font-semibold text-primary bg-primary/5' : 'text-on-surface hover:bg-surface-container' }}">{{ $item->navLabel() }}</a>
                            @endforeach
                            @if ($settings->get('phone') || ($ctaLabel && $ctaUrl))
                                <div class="grid grid-cols-2 gap-2 mt-3 mb-1">
                                    @if ($settings->get('phone'))
                                        <a href="{{ $settings->phoneHref() }}" class="flex items-center justify-center gap-2 py-3 px-3 rounded-lg bg-surface-container-low text-on-surface font-semibold text-sm"><x-icon name="call" class="text-[20px] text-primary" />{{ __('Call') }}</a>
                                    @endif
                                    @if ($ctaLabel && $ctaUrl)
                                        <a href="{{ $ctaUrl }}" class="flex items-center justify-center gap-2 py-3 px-3 rounded-lg bg-btn text-on-btn font-semibold text-sm {{ $settings->get('phone') ? '' : 'col-span-2' }}"><x-icon :name="$settings->get('cta_icon') ?: 'arrow_forward'" class="text-[20px]" />{{ $ctaLabel }}</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </nav>
                </details>
            @endif
        </div>
    </div>
</header>

<main id="content" class="w-full">
    @yield('content')
</main>

@include('theme::partials.footer')

</body>
</html>
