@php
    $image = $r->img($d['image'] ?? null);
    $left = ($d['image_side'] ?? 'right') === 'left';
    $card = ($d['style'] ?? 'split') === 'card';
@endphp
<section class="w-full {{ $r->background($d['background'] ?? 'none') }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 {{ $card ? 'py-8 lg:py-12' : 'py-14 lg:py-24' }}">
        <div class="{{ $card ? 'bg-surface-container-high rounded-2xl overflow-hidden shadow-sm' : '' }} grid grid-cols-1 lg:grid-cols-12 {{ $card ? '' : 'gap-10 lg:gap-12' }} items-center">

            @if ($image || $card)
                <div class="{{ $card ? 'lg:col-span-5 h-64 lg:h-full min-h-[260px]' : 'lg:col-span-5' }} {{ $left ? 'lg:order-first' : 'lg:order-last' }} relative">
                    <div class="relative {{ $card ? 'h-full' : 'rounded-2xl overflow-hidden shadow-lg aspect-square bg-surface-container-high' }}">
                        @if ($image)
                            <img src="{{ $image->url() }}" alt="{{ $image->alt }}" class="w-full h-full object-cover" loading="lazy" width="{{ $image->width }}" height="{{ $image->height }}">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-primary/30 bg-surface-container"><x-icon name="image" class="text-[72px]" /></div>
                        @endif
                        @if (! empty($d['image_quote']))
                            <div class="absolute inset-0 bg-gradient-to-t from-on-surface/60 via-transparent to-transparent"></div>
                            <div class="absolute bottom-5 left-5 right-5 text-white">
                                @if (! empty($d['image_eyebrow']))<span class="text-xs uppercase font-bold tracking-widest opacity-80">{!! $r->t($d['image_eyebrow']) !!}</span>@endif
                                <p class="font-headline text-base font-semibold mt-1">{!! $r->quote($d['image_quote']) !!}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="{{ $image || $card ? 'lg:col-span-7' : 'lg:col-span-12 max-w-3xl' }} space-y-5 {{ $card ? 'p-6 sm:p-8 lg:p-10' : '' }}">
                @if (! empty($d['eyebrow']))
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-primary"><span class="w-6 h-px bg-primary"></span><span>{!! $r->t($d['eyebrow']) !!}</span></div>
                @endif
                @if (! empty($d['title']))
                    <h2 class="font-headline {{ $card ? 'text-xl sm:text-2xl' : 'text-2xl sm:text-3xl lg:text-4xl' }} font-bold text-on-surface leading-snug">{!! $r->t($d['title']) !!}</h2>
                @endif
                @if (! empty($d['text']))
                    <div class="prose-cms {{ $card ? 'text-sm' : 'text-base sm:text-lg' }}">{!! $r->h($d['text']) !!}</div>
                @endif
                @if (! empty($d['note_text']))
                    <div class="p-4 rounded-xl bg-surface-container-highest/60 flex items-center gap-4 text-sm text-secondary">
                        <x-icon :name="$d['note_icon'] ?: 'info'" class="text-[24px] text-primary" /><span>{!! $r->t($d['note_text']) !!}</span>
                    </div>
                @endif
                @if ($checks = $r->lines($d['checks'] ?? ''))
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-medium text-secondary">
                        @foreach ($checks as $check)
                            <span class="flex items-center gap-1"><x-icon name="check_circle" class="text-[16px] text-primary" />{!! $r->t($check) !!}</span>
                        @endforeach
                    </div>
                @endif
                @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? []])
            </div>
        </div>
    </div>
</section>
