<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-stone-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? __('Login') }} · {{ app(\App\Cms\SiteSettings::class)->siteName() }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="min-h-full font-sans text-stone-900 flex items-center justify-center p-4 sm:p-8">
    <main class="w-full max-w-md">
        <div class="text-center mb-8">
            <span class="inline-flex w-14 h-14 rounded-2xl bg-emerald-700 text-white items-center justify-center shadow-sm"><x-icon name="web" class="text-[28px]" /></span>
            <h1 class="mt-4 text-2xl font-bold">{{ $heading ?? app(\App\Cms\SiteSettings::class)->siteName() }}</h1>
            @isset($subheading)<p class="mt-1 text-stone-500">{{ $subheading }}</p>@endisset
        </div>

        <div class="ck-card p-6 sm:p-8">
            @if (session('status'))
                <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</div>
            @endif
            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-stone-500 flex items-center justify-center gap-1.5">
            <x-icon name="lock" class="text-[14px]" />{{ __('Secure login') }} · MyCMS
        </p>
    </main>
</body>
</html>
