{{-- En-tête de section : petit titre, titre, introduction. --}}
@php $center = ($d['align'] ?? 'left') === 'center'; @endphp
@if (! empty($d['eyebrow']) || ! empty($d['title']) || ! empty($d['intro']))
    <div class="{{ $center ? 'text-center max-w-3xl mx-auto' : 'max-w-3xl' }} space-y-3 {{ $mb ?? 'mb-10 lg:mb-14' }}">
        @if (! empty($d['eyebrow']))
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-primary {{ $center ? 'justify-center' : '' }}">
                <span class="w-6 h-px bg-primary"></span><span>{!! $r->t($d['eyebrow']) !!}</span>@if ($center)<span class="w-6 h-px bg-primary"></span>@endif
            </div>
        @endif
        @if (! empty($d['title']))
            <h2 class="font-headline text-2xl sm:text-3xl lg:text-4xl font-bold text-on-surface leading-snug">{!! $r->t($d['title']) !!}</h2>
        @endif
        @if (! empty($d['intro']))
            <p class="text-on-surface-variant text-sm sm:text-base leading-relaxed">{!! $r->t($d['intro']) !!}</p>
        @endif
    </div>
@endif
