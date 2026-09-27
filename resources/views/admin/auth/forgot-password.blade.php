@extends('admin.auth.layout', ['title' => __('Forgotten password'), 'heading' => __('Forgotten password'), 'subheading' => __('Receive a link by e-mail to choose a new one.')])

@section('content')
    <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="ck-label">{{ __('Your e-mail address') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="ck-input">
            @error('email')<p class="ck-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="ck-btn-primary w-full py-3">{{ __('Send the link') }}</button>
        <p class="text-center text-sm"><a href="{{ route('admin.login') }}" class="font-semibold text-emerald-800 hover:underline">{{ __('Back to the login') }}</a></p>
    </form>
@endsection
