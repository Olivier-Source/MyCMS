@extends('admin.auth.layout', ['title' => __('Verification'), 'heading' => __('Two-step verification'), 'subheading' => __('For your security, confirm that it is really you.')])

@section('content')
    <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="space-y-5" x-data="{ recovery: {{ $errors->has('recovery_code') ? 'true' : 'false' }} }">
        @csrf
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-900" role="alert">{{ $errors->first() }}</div>
        @endif

        <div x-show="!recovery">
            <label for="code" class="ck-label">{{ __('6-digit code') }}</label>
            <p class="ck-help mb-3 mt-0">{{ __('Open your authenticator app (Google Authenticator, Microsoft Authenticator…) and type the code shown.') }}</p>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" autofocus
                   class="ck-input text-center text-2xl tracking-[0.5em] font-bold" placeholder="000000" :disabled="recovery">
        </div>

        <div x-show="recovery" x-cloak>
            <label for="recovery_code" class="ck-label">{{ __('Recovery code') }}</label>
            <p class="ck-help mb-3 mt-0">{{ __('Use one of the recovery codes written down when you enabled the protection (each code works only once).') }}</p>
            <input id="recovery_code" name="recovery_code" type="text" autocomplete="off" class="ck-input text-center tracking-widest font-bold uppercase" placeholder="XXXXX-XXXXX" :disabled="!recovery">
        </div>

        <button type="submit" class="ck-btn-primary w-full py-3 text-base">{{ __('Confirm') }}</button>
        <p class="text-center text-sm">
            <button type="button" class="font-semibold text-emerald-800 hover:underline" @click="recovery = !recovery" x-text="recovery ? @js(__('Use the code of the app')) : @js(__('Phone unavailable? Use a recovery code'))"></button>
        </p>
        <p class="text-center text-sm"><a href="{{ route('admin.login') }}" class="text-stone-500 hover:underline">{{ __('Back to the login') }}</a></p>
    </form>
@endsection
