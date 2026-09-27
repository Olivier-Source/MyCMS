@extends('admin.auth.layout', ['title' => __('Login'), 'subheading' => __('Log in to edit your site.')])

@section('content')
    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
        @csrf
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-900" role="alert">{{ $errors->first() }}</div>
        @endif
        <div>
            <label for="email" class="ck-label">{{ __('E-mail address') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="ck-input">
        </div>
        <div x-data="{ show: false }">
            <label for="password" class="ck-label">{{ __('Password') }}</label>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password" class="ck-input pr-12">
                <button type="button" class="ck-icon-btn absolute right-1.5 top-1/2 -translate-y-1/2" @click="show = !show" :aria-label="show ? @js(__('Hide the password')) : @js(__('Show the password'))">
                    <x-icon name="visibility" class="text-[20px]" />
                </button>
            </div>
        </div>
        <button type="submit" class="ck-btn-primary w-full py-3 text-base">{{ __('Log in') }}</button>
        <p class="text-center text-sm">
            <a href="{{ route('admin.password.request') }}" class="font-semibold text-emerald-800 hover:underline">{{ __('Forgot your password?') }}</a>
        </p>
    </form>
@endsection
