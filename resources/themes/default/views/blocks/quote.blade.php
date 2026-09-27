<section class="w-full {{ $r->background($d['background'] ?? 'soft') }} py-14 lg:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center space-y-4">
        <x-icon name="format_quote" class="text-[40px] text-primary/60" />
        <blockquote class="font-headline text-xl sm:text-2xl lg:text-3xl font-medium text-on-surface leading-snug">{!! $r->quote($d['quote'] ?? '') !!}</blockquote>
        @if (! empty($d['author']))
            <p class="text-sm font-semibold tracking-wide uppercase text-tertiary">{!! $r->t($d['author']) !!}</p>
        @endif
    </div>
</section>
