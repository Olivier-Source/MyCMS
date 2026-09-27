{{-- Generic error page (403, 419, 429…). $code, $title and $message are provided by resources/views/errors. --}}
@extends('theme::layouts.site')

@section('content')
    <section class="min-h-[60vh] flex items-center">
        <div class="max-w-xl mx-auto px-4 sm:px-6 py-16 text-center space-y-5">
            <p class="font-headline text-6xl font-bold text-primary/40">{{ $code }}</p>
            <h1 class="font-headline text-3xl sm:text-4xl font-bold text-on-surface">{{ $title }}</h1>
            <p class="text-on-surface-variant">{{ $message }}</p>
            <div class="pt-2">
                <a href="{{ $homeUrl }}" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-btn text-on-btn font-semibold text-sm hover:bg-btn-hover shadow-md transition-all"><x-icon name="home" class="text-[20px]" />{{ __('Back to the home page') }}</a>
            </div>
        </div>
    </section>
@endsection
