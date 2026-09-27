{{--
    The "Minimal" theme redefines only this section: a big centered title
    and a wide picture underneath. It uses the same fields as the default
    hero (see app/Cms/BlockRegistry.php → "hero"), so switching themes keeps
    all the content.
--}}
@php
    $image = $r->img($d['image'] ?? null);
    $badges = array_filter($d['badges'] ?? [], fn ($b) => ! empty($b['text']));
@endphp
<section class="max-w-6xl mx-auto px-5 sm:px-8 pt-16 sm:pt-24 pb-12 text-center">
    @if ($d['show_breadcrumb'] ?? false)
        <div class="flex justify-center">@include('theme::blocks.partials.breadcrumb', ['class' => 'mb-8'])</div>
    @endif
    @if (! empty($d['badge']))
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-tertiary mb-6">{!! $r->t($d['badge']) !!}</p>
    @endif
    <h1 class="font-headline text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight leading-[1.05] text-on-surface max-w-4xl mx-auto">
        {!! $r->t($d['title'] ?? '') !!}
    </h1>
    @if (! empty($d['subtitle']))
        <p class="mt-5 text-xl sm:text-2xl text-on-surface-variant max-w-2xl mx-auto">{!! $r->t($d['subtitle']) !!}</p>
    @endif
    @if (! empty($d['text']))
        <p class="mt-6 text-base sm:text-lg text-on-surface-variant leading-relaxed max-w-2xl mx-auto">{!! $r->t($d['text']) !!}</p>
    @endif
    @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? [], 'class' => 'mt-10 flex flex-col sm:flex-row justify-center gap-3'])

    @if ($badges)
        <ul class="mt-12 flex flex-wrap justify-center gap-x-10 gap-y-4 text-sm">
            @foreach ($badges as $badge)
                <li class="flex items-center gap-2">
                    <x-icon :name="$badge['icon'] ?: 'check'" class="text-[18px] text-tertiary" />
                    <span>@if (! empty($badge['title']))<strong>{!! $r->t($badge['title']) !!}</strong> — @endif{!! $r->t($badge['text']) !!}</span>
                </li>
            @endforeach
        </ul>
    @endif
</section>

@if ($image && ($d['layout'] ?? 'card') !== 'simple')
    <figure class="max-w-6xl mx-auto px-5 sm:px-8 pb-16">
        <img src="{{ $image->url() }}" alt="{{ $image->alt }}" width="{{ $image->width }}" height="{{ $image->height }}" class="w-full aspect-[21/9] object-cover rounded-2xl">
        @if (! empty($d['image_title']) || ! empty($d['image_text']))
            <figcaption class="mt-3 text-sm text-on-surface-variant text-center">{!! $r->t(trim(($d['image_title'] ?? '').' — '.($d['image_text'] ?? ''), ' —')) !!}</figcaption>
        @endif
    </figure>
@endif
