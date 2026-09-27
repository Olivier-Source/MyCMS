@extends('admin.auth.layout', ['title' => __('New password'), 'heading' => __('Choose a new password')])

@section('content')
    <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-900" role="alert">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <div>
            <label for="email" class="ck-label">{{ __('E-mail address') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="username" class="ck-input">
        </div>
        <div>
            <label for="password" class="ck-label">{{ __('New password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="ck-input">
            <p class="ck-help">{{ __('12 characters minimum, with upper and lower case letters and digits. A sentence is easier to remember: "My-cat-sleeps-7-times".') }}</p>
        </div>
        <div>
            <label for="password_confirmation" class="ck-label">{{ __('Confirm the password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="ck-input">
        </div>
        <button type="submit" class="ck-btn-primary w-full py-3">{{ __('Save') }}</button>
    </form>
@endsection
