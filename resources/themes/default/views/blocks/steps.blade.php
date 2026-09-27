@php
    $big = ($d['style'] ?? 'numbers') === 'cards';
    $cols = match ($d['columns'] ?? '3') { '4' => 'md:grid-cols-2 lg:grid-cols-4', '2' => 'md:grid-cols-2', default => 'md:grid-cols-3' };
    $cardBg = ($d['background'] ?? 'none') === 'none' ? 'bg-surface-container-low' : 'bg-surface-container-lowest shadow-sm';
@endphp
<section class="w-full {{ $r->background($d['background'] ?? 'none') }} py-14 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
        @include('theme::blocks.partials.heading')

        <ol class="grid grid-cols-1 {{ $cols }} gap-4 sm:gap-6 lg:gap-8">
            @foreach ($d['items'] ?? [] as $i => $item)
                @php $tone = $item['tone'] ?? 'primary'; $num = str_pad($i + 1, 2, '0', STR_PAD_LEFT); @endphp
                <li class="group p-6 {{ $big ? 'sm:p-8' : '' }} rounded-2xl {{ $cardBg }} flex flex-col justify-between gap-4 hover:-translate-y-0.5 transition-all">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            @if ($big)
                                <span class="font-headline text-3xl font-bold text-primary/40 group-hover:text-primary transition-colors">{{ $num }}</span>
                            @else
                                <span class="w-10 h-10 rounded-full font-bold font-headline flex items-center justify-center text-sm shadow-sm {{ $r->tone($tone, 'solid') }}">{{ $num }}</span>
                            @endif
                            @if (! empty($item['tag']))
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-secondary-container text-on-secondary-container">{!! $r->t($item['tag']) !!}</span>
                            @elseif (! empty($item['icon']))
                                <x-icon :name="$item['icon']" class="text-[28px] text-primary" />
                            @endif
                        </div>
                        <h3 class="font-headline text-lg {{ $big ? 'sm:text-xl' : '' }} font-bold text-on-surface leading-snug">{!! $r->t($item['title'] ?? '') !!}</h3>
                        @if (! empty($item['label']))<p class="text-xs text-tertiary font-semibold uppercase tracking-wider">{!! $r->t($item['label']) !!}</p>@endif
                        <p class="text-sm text-on-surface-variant leading-relaxed">{!! $r->t($item['text'] ?? '') !!}</p>
                    </div>
                    @if (! empty($item['footer_text']))
                        <div class="pt-2 flex items-center gap-2 text-xs font-semibold {{ $r->tone($tone, 'text') }}">
                            <x-icon :name="$item['footer_icon'] ?: 'check_circle'" class="text-[18px]" /><span>{!! $r->t($item['footer_text']) !!}</span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>

        @if (! empty($d['bar_title']) || ! empty($d['bar_text']))
            <div class="mt-10 lg:mt-12 p-6 rounded-2xl bg-surface-container-low flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-surface-container-highest flex items-center justify-center shrink-0 text-primary"><x-icon :name="$d['bar_icon'] ?: 'lock'" class="text-[24px]" /></div>
                    <div>
                        <h4 class="font-headline font-semibold text-on-surface text-base">{!! $r->t($d['bar_title']) !!}</h4>
                        <p class="text-xs sm:text-sm text-secondary">{!! $r->t($d['bar_text']) !!}</p>
                    </div>
                </div>
                @if (! empty($d['bar_link_label']))
                    <a {!! $r->linkAttrs($d['bar_link_url']) !!} class="shrink-0 text-xs sm:text-sm font-semibold text-primary hover:opacity-80 flex items-center gap-1 underline underline-offset-4">{!! $r->t($d['bar_link_label']) !!}<x-icon name="arrow_forward" class="text-[16px]" /></a>
                @endif
            </div>
        @endif
    </div>
</section>
