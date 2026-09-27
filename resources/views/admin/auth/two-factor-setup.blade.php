@extends('admin.auth.layout', ['title' => __('Two-factor authentication'), 'heading' => __('Protect your account'), 'subheading' => $required ? __('A mandatory step, to do only once (2 minutes).') : __('To do only once (2 minutes).')])

@section('content')
    <ol class="space-y-6">
        <li class="flex gap-4">
            <span class="w-8 h-8 shrink-0 rounded-full bg-emerald-700 text-white font-bold flex items-center justify-center">1</span>
            <div>
                <p class="font-semibold">{{ __('Install an authenticator app') }}</p>
                <p class="ck-help">{{ __('On your phone: Google Authenticator, Microsoft Authenticator or 2FAS (free, on the App Store or Google Play).') }}</p>
            </div>
        </li>
        <li class="flex gap-4">
            <span class="w-8 h-8 shrink-0 rounded-full bg-emerald-700 text-white font-bold flex items-center justify-center">2</span>
            <div class="min-w-0">
                <p class="font-semibold">{{ __('Scan this code with the app') }}</p>
                <div class="mt-3 inline-block rounded-2xl border border-stone-200 bg-white p-3 [&_svg]:w-48 [&_svg]:h-48">{!! $qr !!}</div>
                <details class="mt-2">
                    <summary class="text-sm font-semibold text-emerald-800 cursor-pointer">{{ __('Cannot scan? Type the key by hand') }}</summary>
                    <p class="mt-2 font-mono text-sm bg-stone-100 rounded-lg px-3 py-2 break-all select-all">{{ $secret }}</p>
                </details>
            </div>
        </li>
        <li class="flex gap-4">
            <span class="w-8 h-8 shrink-0 rounded-full bg-emerald-700 text-white font-bold flex items-center justify-center">3</span>
            <form method="POST" action="{{ route('admin.two-factor.confirm') }}" class="flex-1 space-y-3">
                @csrf
                <label for="code" class="font-semibold block">{{ __('Type the 6-digit code shown') }}</label>
                <input id="code" name="code" type="text" inputmode="numeric" maxlength="7" autocomplete="one-time-code" required
                       class="ck-input text-center text-2xl tracking-[0.5em] font-bold" placeholder="000000">
                @error('code')<p class="ck-error">{{ $message }}</p>@enderror
                <button type="submit" class="ck-btn-primary w-full py-3">{{ __('Enable the protection') }}</button>
            </form>
        </li>
    </ol>
    @if ($required)
        <form method="POST" action="{{ route('admin.logout') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-sm text-stone-500 hover:underline">{{ __('Log out and do it later') }}</button>
        </form>
    @else
        <p class="mt-6 text-center"><a href="{{ route('admin.dashboard') }}" class="text-sm text-stone-500 hover:underline">{{ __('Not now') }}</a></p>
    @endif
@endsection
