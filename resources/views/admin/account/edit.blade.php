@extends('admin.layout', ['title' => __('My account & security')])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('My account & security') }}</h1>
        <p class="mt-1 text-stone-500">{{ __('Your login, your password, your language and the protection of your account.') }}</p>
    </div>

    @if (session('recovery_codes'))
        <section class="mb-6 rounded-2xl border-2 border-amber-300 bg-amber-50 p-5 sm:p-6">
            <h2 class="text-lg font-bold flex items-center gap-2 text-amber-900"><x-icon name="key" class="text-[22px]" />{{ __('Your recovery codes — keep them safe') }}</h2>
            <p class="mt-1 text-sm text-amber-900">{{ __('If you lose your phone, each of these codes lets you log in only once. Print them or write them down in a safe place: they will not be shown again.') }}</p>
            <ul class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-sm">
                @foreach (session('recovery_codes') as $code)
                    <li class="rounded-lg bg-white border border-amber-200 px-3 py-2 text-center select-all">{{ \App\Cms\TwoFactor::formatCode($code) }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <section class="ck-card p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-4">{{ __('My profile') }}</h2>
            <form method="POST" action="{{ route('admin.account.profile') }}" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label for="name" class="ck-label">{{ __('Name') }}</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" class="ck-input">
                </div>
                <div>
                    <label for="email" class="ck-label">{{ __('Login e-mail address') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="ck-input">
                </div>
                <div>
                    <label for="locale" class="ck-label">{{ __('Language of the administration') }}</label>
                    <select id="locale" name="locale" class="ck-input">
                        <option value="">{{ __('Default language') }}</option>
                        @foreach ($languages as $code => $name)
                            <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="current_password_profile" class="ck-label">{{ __('Current password (to confirm)') }}</label>
                    <input id="current_password_profile" name="current_password" type="password" required autocomplete="current-password" class="ck-input">
                </div>
                <button type="submit" class="ck-btn-primary">{{ __('Save') }}</button>
            </form>
        </section>

        <section class="ck-card p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-1">{{ __('Change my password') }}</h2>
            <p class="ck-help mt-0 mb-4">
                @if ($user->password_changed_at){{ __('Last change: :date.', ['date' => $user->password_changed_at->translatedFormat('j F Y')]) }}@endif
                {{ __('Your other devices will be logged out.') }}
            </p>
            <form method="POST" action="{{ route('admin.account.password') }}" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label for="current_password" class="ck-label">{{ __('Current password') }}</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="ck-input">
                </div>
                <div>
                    <label for="password" class="ck-label">{{ __('New password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="ck-input">
                    <p class="ck-help">{{ __('12 characters minimum, with upper and lower case letters and digits. A sentence is easier to remember: "My-cat-sleeps-7-times".') }}</p>
                </div>
                <div>
                    <label for="password_confirmation" class="ck-label">{{ __('Confirm the new password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="ck-input">
                </div>
                <button type="submit" class="ck-btn-primary">{{ __('Change the password') }}</button>
            </form>
        </section>

        <section class="ck-card p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-1 flex items-center gap-2"><x-icon name="verified_user" class="text-[22px] text-emerald-700" />{{ __('Two-factor authentication') }}</h2>
            @if ($user->hasTwoFactorEnabled())
                <p class="text-sm text-stone-600">{{ __('Enabled on :date. At each login, a code from your app is asked.', ['date' => $user->two_factor_confirmed_at?->translatedFormat('j F Y')]) }}</p>
                <p class="mt-3 text-sm">{{ __('Recovery codes left:') }} <strong>{{ count($user->two_factor_recovery_codes ?? []) }}</strong> / 8</p>
                <form method="POST" action="{{ route('admin.account.codes') }}" class="mt-4 space-y-3" x-data="{ open: false }">
                    @csrf
                    <button type="button" class="ck-btn-secondary" x-show="!open" @click="open = true"><x-icon name="refresh" class="text-[18px]" />{{ __('Generate new recovery codes') }}</button>
                    <div x-show="open" x-cloak class="space-y-3">
                        <label for="current_password_codes" class="ck-label">{{ __('Current password') }}</label>
                        <input id="current_password_codes" name="current_password" type="password" autocomplete="current-password" class="ck-input">
                        <button type="submit" class="ck-btn-primary">{{ __('Generate (the old codes will no longer work)') }}</button>
                    </div>
                </form>
                @unless ($require2fa)
                    <form method="POST" action="{{ route('admin.account.two-factor.disable') }}" class="mt-4 space-y-3" x-data="{ open: false }">
                        @csrf
                        <button type="button" class="ck-btn-ghost text-red-700" x-show="!open" @click="open = true">{{ __('Disable two-factor authentication') }}</button>
                        <div x-show="open" x-cloak class="space-y-3">
                            <label for="current_password_2fa" class="ck-label">{{ __('Current password') }}</label>
                            <input id="current_password_2fa" name="current_password" type="password" autocomplete="current-password" class="ck-input">
                            <button type="submit" class="ck-btn-danger">{{ __('Disable') }}</button>
                        </div>
                    </form>
                @endunless
                <p class="ck-help mt-4">{{ __('Lost phone and no recovery code left? The server administrator can reset the protection with "php artisan mycms:admin your@email --reset-2fa".') }}</p>
            @else
                <p class="text-sm text-stone-600">{{ __('Not enabled. With an authenticator app on your phone, nobody can log in with your password alone.') }}</p>
                <a href="{{ route('admin.two-factor.setup') }}" class="ck-btn-primary mt-4"><x-icon name="verified_user" class="text-[18px]" />{{ __('Enable it (2 minutes)') }}</a>
            @endif
        </section>

        <section class="ck-card p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-3">{{ __('Signed-in devices') }}</h2>
            <ul class="divide-y divide-stone-100">
                @foreach ($sessions as $session)
                    @php
                        $agent = (string) $session->user_agent;
                        $mobile = str_contains($agent, 'Mobile');
                        $browser = collect(['Edg' => 'Edge', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari'])->first(fn ($v, $k) => str_contains($agent, $k)) ?? __('Browser');
                    @endphp
                    <li class="flex items-center gap-3 py-3">
                        <x-icon :name="$mobile ? 'smartphone' : 'computer'" class="text-[24px] text-stone-400" />
                        <span class="flex-1 min-w-0 text-sm">
                            <span class="block font-semibold">{{ $mobile ? __('Phone') : __('Computer') }} · {{ $browser }} @if ($session->id === $currentSession)<span class="ck-badge bg-emerald-50 text-emerald-800 ml-1">{{ __('this device') }}</span>@endif</span>
                            <span class="block text-stone-500">{{ $session->ip_address }} · {{ __('active :time', ['time' => \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity)->diffForHumans()]) }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
            @if ($sessions->count() > 1)
                <form method="POST" action="{{ route('admin.account.sessions') }}" class="mt-4 space-y-3" x-data="{ open: false }">
                    @csrf
                    <button type="button" class="ck-btn-secondary" x-show="!open" @click="open = true"><x-icon name="logout" class="text-[18px]" />{{ __('Log out the other devices') }}</button>
                    <div x-show="open" x-cloak class="space-y-3">
                        <label for="current_password_sessions" class="ck-label">{{ __('Current password') }}</label>
                        <input id="current_password_sessions" name="current_password" type="password" autocomplete="current-password" class="ck-input">
                        <button type="submit" class="ck-btn-primary">{{ __('Log out the other devices') }}</button>
                    </div>
                </form>
            @endif
        </section>
    </div>
@endsection
