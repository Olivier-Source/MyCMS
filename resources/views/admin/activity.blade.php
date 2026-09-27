@extends('admin.layout', ['title' => __('Activity log')])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Activity log') }}</h1>
        <p class="mt-1 text-stone-500">{{ __('Logins, failed attempts and changes of the site, kept for one year.') }}</p>
    </div>

    @php
        $style = fn ($action) => match (true) {
            str_starts_with($action, 'login.failed'), str_starts_with($action, 'login.locked') => ['warning', 'text-amber-600 bg-amber-50'],
            str_starts_with($action, 'login'), str_starts_with($action, 'logout') => ['login', 'text-sky-700 bg-sky-50'],
            str_contains($action, 'deleted') => ['delete', 'text-red-700 bg-red-50'],
            str_starts_with($action, '2fa'), str_starts_with($action, 'password'), str_starts_with($action, 'account'), str_starts_with($action, 'admin') => ['shield_lock', 'text-violet-700 bg-violet-50'],
            str_starts_with($action, 'theme'), str_starts_with($action, 'language') => ['extension', 'text-fuchsia-700 bg-fuchsia-50'],
            default => ['edit', 'text-emerald-700 bg-emerald-50'],
        };
    @endphp

    <div class="ck-card divide-y divide-stone-100">
        @forelse ($logs as $log)
            @php [$icon, $classes] = $style($log->action); @endphp
            <div class="flex items-center gap-4 px-4 sm:px-5 py-3.5">
                <span class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center {{ $classes }}"><x-icon :name="$icon" class="text-[18px]" /></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium">{{ $log->description }}</p>
                    <p class="text-xs text-stone-500">{{ $log->user?->name ?? __('System / unknown') }}{{ $log->ip ? ' · '.$log->ip : '' }}</p>
                </div>
                <time class="text-xs text-stone-400 shrink-0 text-right" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->translatedFormat('j M Y') }}<span class="block">{{ $log->created_at->format('H:i') }}</span></time>
            </div>
        @empty
            <p class="p-8 text-center text-stone-500">{{ __('No activity recorded.') }}</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $logs->links() }}</div>
@endsection
