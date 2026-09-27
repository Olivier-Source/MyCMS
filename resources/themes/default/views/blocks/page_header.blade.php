<section class="bg-surface-container-low">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-14 space-y-4">
        @include('theme::blocks.partials.breadcrumb', ['class' => 'mb-2'])
        <h1 class="font-headline text-3xl sm:text-4xl font-bold text-on-surface">{!! $r->t($d['title'] ?? '') !!}</h1>
        @if (! empty($d['text']))
            <p class="text-on-surface-variant leading-relaxed">{!! $r->t($d['text']) !!}</p>
        @endif
        @if (! empty($d['note']))
            <p class="text-sm text-on-surface-variant">{!! $r->t($d['note']) !!}</p>
        @endif
    </div>
</section>
