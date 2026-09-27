<section class="w-full {{ $r->background($d['background'] ?? 'none') }} py-14 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16">
        <div class="lg:col-span-5 space-y-4">
            @if (! empty($d['eyebrow']))<span class="block text-xs font-bold uppercase tracking-widest text-primary">{!! $r->t($d['eyebrow']) !!}</span>@endif
            <h2 class="font-headline text-2xl sm:text-3xl lg:text-4xl font-bold text-on-surface">{!! $r->t($d['title'] ?? '') !!}</h2>
            @if (! empty($d['text']))<p class="text-on-surface-variant leading-relaxed">{!! $r->t($d['text']) !!}</p>@endif
        </div>
        <div class="lg:col-span-7 relative">
            <div class="p-5 sm:p-8 rounded-2xl bg-surface-container shadow-md">
                @include('theme::blocks.partials.contact-form')
            </div>
        </div>
    </div>
</section>
