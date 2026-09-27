@php
    $unreadCount = \App\Models\ContactMessage::whereNull('read_at')->count();
    $nav = [
        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => __('Dashboard')],
        ['route' => 'admin.pages.index', 'match' => 'admin.pages.*|admin.blocks.*', 'icon' => 'article', 'label' => __('Pages')],
        ['route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'icon' => 'badge', 'label' => __('Site information')],
        ['route' => 'admin.appearance.edit', 'match' => 'admin.appearance.*', 'icon' => 'palette', 'label' => __('Colours & style')],
        ['route' => 'admin.themes.index', 'match' => 'admin.themes.*', 'icon' => 'extension', 'label' => __('Themes')],
        ['route' => 'admin.languages.index', 'match' => 'admin.languages.*', 'icon' => 'translate', 'label' => __('Languages')],
        ['route' => 'admin.media.index', 'match' => 'admin.media.*', 'icon' => 'photo_library', 'label' => __('Pictures')],
        ['route' => 'admin.messages.index', 'match' => 'admin.messages.*', 'icon' => 'mail', 'label' => __('Messages'), 'count' => $unreadCount],
    ];
    $navBottom = [
        ['route' => 'admin.account.edit', 'match' => 'admin.account.*', 'icon' => 'shield_lock', 'label' => __('My account & security')],
        ['route' => 'admin.activity', 'match' => 'admin.activity', 'icon' => 'history', 'label' => __('Activity log')],
        ['route' => 'admin.help', 'match' => 'admin.help', 'icon' => 'help', 'label' => __('Help')],
    ];
    $isActive = fn ($match) => collect(explode('|', $match))->contains(fn ($m) => request()->routeIs($m));
    $siteName = app(\App\Cms\SiteSettings::class)->siteName();
    // Addresses and translated texts used by resources/js/admin.js
    $adminConfig = [
        'mediaList' => route('admin.media.list'),
        'mediaUpload' => route('admin.media.store'),
        'icons' => route('admin.icons'),
        'i18n' => [
            'loadError' => __('The media library could not be loaded.'),
            'uploadError' => __('Upload failed.'),
            'removeItem' => __('Remove this item?'),
            'orderError' => __('The order could not be saved:'),
            'error' => __('Error'),
            'readability' => [
                'text' => __('the main text on the page background'),
                'muted' => __('the secondary text on the page background'),
                'card' => __('the text on the card background'),
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-stone-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim(($title ?? '').' · '.$siteName, ' ·') }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    <script type="application/json" id="admin-config">@json($adminConfig)</script>
</head>
<body class="h-full font-sans text-stone-900" x-data="shell">

<div class="min-h-full lg:flex">
    {{-- Sidebar --}}
    <div x-show="menu" x-transition.opacity class="fixed inset-0 z-40 bg-stone-900/40 lg:hidden" @click="menu = false" x-cloak></div>
    <aside class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-stone-200 flex flex-col transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
           :class="menu ? 'translate-x-0' : '-translate-x-full'" x-cloak>
        <div class="px-5 pt-6 pb-4 flex items-center justify-between">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 shrink-0 rounded-xl bg-emerald-700 text-white flex items-center justify-center"><x-icon name="web" class="text-[22px]" /></span>
                <span class="min-w-0">
                    <span class="block font-bold leading-tight truncate">{{ $siteName }}</span>
                    <span class="block text-xs text-stone-500">{{ __('Administration') }}</span>
                </span>
            </a>
            <button type="button" class="ck-icon-btn lg:hidden" @click="menu = false" aria-label="{{ __('Close the menu') }}"><x-icon name="close" class="text-[22px]" /></button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-1" aria-label="{{ __('Administration') }}">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" class="ck-nav-link {{ $isActive($item['match']) ? 'is-active' : '' }}">
                    <x-icon :name="$item['icon']" class="text-[22px]" />
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if (! empty($item['count']))
                        <span class="ck-badge bg-emerald-600 text-white">{{ $item['count'] }}</span>
                    @endif
                </a>
            @endforeach
            <div class="my-3 border-t border-stone-200"></div>
            @foreach ($navBottom as $item)
                <a href="{{ route($item['route']) }}" class="ck-nav-link {{ $isActive($item['match']) ? 'is-active' : '' }}">
                    <x-icon :name="$item['icon']" class="text-[22px]" /><span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="p-4 border-t border-stone-200 space-y-2">
            <a href="{{ site_url('/') }}" target="_blank" rel="noopener" class="ck-btn-secondary w-full"><x-icon name="open_in_new" class="text-[18px]" />{{ __('View my site') }}</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="ck-btn-ghost w-full"><x-icon name="logout" class="text-[18px]" />{{ __('Log out') }}</button>
            </form>
            <p class="pt-1 text-center text-[11px] text-stone-400">MyCMS {{ config('mycms.version') }}</p>
        </div>
    </aside>

    {{-- Content --}}
    <div class="flex-1 min-w-0">
        <header class="lg:hidden sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-stone-200 px-4 h-14 flex items-center justify-between">
            <button type="button" class="ck-icon-btn" @click="menu = true" aria-label="{{ __('Open the menu') }}"><x-icon name="menu" class="text-[24px]" /></button>
            <span class="font-bold truncate px-2">{{ $title ?? __('Administration') }}</span>
            <a href="{{ site_url('/') }}" target="_blank" rel="noopener" class="ck-icon-btn" aria-label="{{ __('View my site') }}"><x-icon name="open_in_new" class="text-[20px]" /></a>
        </header>

        <main class="px-4 sm:px-6 lg:px-10 py-6 lg:py-10 max-w-6xl mx-auto">
            @if (session('status'))
                <div class="mb-6 flex items-start gap-3 rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-900" role="status"
                     x-data="{ show: true }" x-show="show" x-transition>
                    <x-icon name="check_circle" class="text-[22px] text-emerald-600 mt-0.5" />
                    <p class="flex-1 text-[15px]">{{ session('status') }}</p>
                    <button type="button" class="ck-icon-btn -my-1 h-8 w-8" @click="show = false" aria-label="{{ __('Close') }}"><x-icon name="close" class="text-[18px]" /></button>
                </div>
            @endif
            @if (session('warning'))
                <div class="mb-6 flex items-start gap-3 rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3 text-amber-900" role="status">
                    <x-icon name="warning" class="text-[22px] text-amber-600 mt-0.5" /><p class="text-[15px]">{{ session('warning') }}</p>
                </div>
            @endif
            @if ($errors->any() && ! ($inlineErrors ?? false))
                <div class="mb-6 flex items-start gap-3 rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-red-900" role="alert">
                    <x-icon name="error" class="text-[22px] text-red-600 mt-0.5" />
                    <div class="text-[15px]">
                        <p class="font-semibold">{{ __('Something could not be saved:') }}</p>
                        <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

{{-- Media library (picking an image) --}}
<div x-data x-show="$store.media.open" x-cloak class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-6" @keydown.escape.window="$store.media.open = false">
    <div class="absolute inset-0 bg-stone-900/50" @click="$store.media.open = false"></div>
    <div class="relative w-full sm:max-w-4xl max-h-[90vh] flex flex-col bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl" x-trap.noscroll="$store.media.open" role="dialog" aria-modal="true" aria-label="{{ __('Choose an image') }}">
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-stone-200">
            <h2 class="text-lg font-bold">{{ __('Choose an image') }}</h2>
            <button type="button" class="ck-icon-btn" @click="$store.media.open = false" aria-label="{{ __('Close') }}"><x-icon name="close" class="text-[22px]" /></button>
        </div>
        <div class="px-5 py-3 flex flex-col sm:flex-row gap-3 sm:items-center border-b border-stone-100">
            <label class="ck-btn-primary cursor-pointer">
                <x-icon name="upload" class="text-[18px]" /><span x-text="$store.media.uploading ? @js(__('Uploading…')) : @js(__('Upload a new image'))"></span>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="$store.media.upload($event)" :disabled="$store.media.uploading">
            </label>
            <input type="search" x-model="$store.media.search" class="ck-input sm:max-w-xs" placeholder="{{ __('Search…') }}">
            <p class="text-xs text-stone-500">{{ __('JPG, PNG or WebP · :max MB max.', ['max' => (int) round(config('mycms.media.max_kb') / 1024)]) }}</p>
        </div>
        <p x-show="$store.media.error" x-text="$store.media.error" class="mx-5 mt-3 ck-error"></p>
        <div class="flex-1 overflow-y-auto p-5">
            <p x-show="$store.media.loading" class="text-stone-500">{{ __('Loading…') }}</p>
            <p x-show="!$store.media.loading && !$store.media.filtered.length" class="text-stone-500">{{ __('No image yet: upload one with the button above.') }}</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <template x-for="item in $store.media.filtered" :key="item.id">
                    <button type="button" @click="$store.media.choose(item)" class="group text-left rounded-xl border border-stone-200 overflow-hidden hover:border-emerald-600 hover:ring-4 hover:ring-emerald-600/15 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-600/25">
                        <img :src="item.url" :alt="item.alt || ''" class="w-full aspect-[4/3] object-cover bg-stone-100" loading="lazy">
                        <span class="block px-2.5 py-2 text-xs text-stone-600 truncate" x-text="item.alt || item.name"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- Icon picker --}}
<div x-data x-show="$store.icons.open" x-cloak class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-6" @keydown.escape.window="$store.icons.open = false">
    <div class="absolute inset-0 bg-stone-900/50" @click="$store.icons.open = false"></div>
    <div class="relative w-full sm:max-w-3xl max-h-[85vh] flex flex-col bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl" x-trap.noscroll="$store.icons.open" role="dialog" aria-modal="true" aria-label="{{ __('Choose an icon') }}">
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-stone-200">
            <h2 class="text-lg font-bold">{{ __('Choose an icon') }}</h2>
            <button type="button" class="ck-icon-btn" @click="$store.icons.open = false" aria-label="{{ __('Close') }}"><x-icon name="close" class="text-[22px]" /></button>
        </div>
        <div class="px-5 py-3 border-b border-stone-100">
            <input type="search" x-model="$store.icons.search" class="ck-input" placeholder="{{ __('Search in English: heart, calendar, person, shop…') }}">
        </div>
        <div class="flex-1 overflow-y-auto p-5">
            <div class="grid grid-cols-5 sm:grid-cols-8 gap-2">
                <template x-for="name in $store.icons.names" :key="name">
                    <button type="button" @click="$store.icons.choose(name)" :title="name"
                            class="aspect-square rounded-xl border border-stone-200 flex items-center justify-center text-stone-700 hover:border-emerald-600 hover:bg-emerald-50 hover:text-emerald-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-600/25">
                        <svg viewBox="0 -960 960 960" class="w-7 h-7" fill="currentColor" aria-hidden="true"><path :d="$store.icons.paths[name]"></path></svg>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

</body>
</html>
