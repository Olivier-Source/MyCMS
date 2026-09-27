@extends('theme::layouts.site')

@section('content')
    <section class="min-h-[60vh] flex items-center">
        <div class="max-w-xl mx-auto px-4 sm:px-6 py-16 text-center space-y-5">
            <div class="w-20 h-20 mx-auto rounded-full bg-primary-fixed/50 text-on-primary-fixed flex items-center justify-center"><x-icon name="explore_off" class="text-[40px]" /></div>
            <h1 class="font-headline text-3xl sm:text-4xl font-bold text-on-surface">{{ __('This page cannot be found') }}</h1>
            <p class="text-on-surface-variant">{{ __('The link may be wrong, or the page may have moved.') }}</p>
            <div class="flex flex-col sm:flex-row justify-center gap-3 pt-2">
                <a href="{{ $homeUrl }}" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-btn text-on-btn font-semibold text-sm hover:bg-btn-hover shadow-md transition-all"><x-icon name="home" class="text-[20px]" />{{ __('Back to the home page') }}</a>
                @if ($ctaLabel && $ctaUrl)
                    <a href="{{ $ctaUrl }}" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-surface-container-high text-on-surface font-semibold text-sm hover:bg-surface-variant transition-colors"><x-icon :name="$settings->get('cta_icon') ?: 'arrow_forward'" class="text-[20px] text-primary" />{{ $ctaLabel }}</a>
                @endif
            </div>
        </div>
    </section>
@endsection
