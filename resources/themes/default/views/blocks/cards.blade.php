@php
    $style = $d['style'] ?? 'vertical';
    $items = $d['items'] ?? [];
    $cardBg = ($d['background'] ?? 'none') === 'none' ? 'bg-surface-container-low' : 'bg-surface-container-lowest';
@endphp
<section class="w-full {{ $r->background($d['background'] ?? 'none') }} py-14 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
        @include('theme::blocks.partials.heading')

        <div class="grid grid-cols-1 {{ $r->columns($d['columns'] ?? '3') }} gap-4 sm:gap-6">
            @foreach ($items as $item)
                @php
                    $tone = $item['tone'] ?? 'primary';
                    $image = $r->img($item['image'] ?? null);
                    $hasLink = ! empty($item['link_url']);
                    $tag = $hasLink ? 'a' : 'div';
                @endphp

                @if ($style === 'horizontal')
                    <{{ $tag }} @if ($hasLink) {!! $r->linkAttrs($item['link_url']) !!} @endif class="group p-5 sm:p-6 rounded-2xl {{ $cardBg }} shadow-sm hover:shadow-md transition-all flex items-start gap-4">
                        @if (! empty($item['icon']))
                            <div class="p-2.5 rounded-xl shrink-0 {{ $r->tone($tone, 'soft') }}"><x-icon :name="$item['icon']" class="text-[22px] block" /></div>
                        @endif
                        <div class="space-y-1">
                            <h3 class="font-headline text-base font-bold text-on-surface">{!! $r->t($item['title'] ?? '') !!}</h3>
                            @if (! empty($item['text']))<p class="text-xs sm:text-sm text-on-surface-variant leading-relaxed">{!! $r->t($item['text']) !!}</p>@endif
                            @if (! empty($item['link_label']))<span class="inline-flex items-center gap-1 pt-1 text-xs font-semibold {{ $r->tone($tone, 'text') }}">{!! $r->t($item['link_label']) !!}<x-icon name="arrow_forward" class="text-[14px] group-hover:translate-x-1 transition-transform" /></span>@endif
                        </div>
                    </{{ $tag }}>

                @elseif ($style === 'media')
                    <div class="{{ $cardBg }} rounded-2xl p-6 sm:p-8 flex flex-col justify-between gap-6">
                        <div class="space-y-4">
                            @if ($image)
                                <div class="w-full h-52 rounded-xl overflow-hidden relative shadow-sm">
                                    <img src="{{ $image->url() }}" alt="{{ $image->alt }}" class="w-full h-full object-cover" loading="lazy">
                                    @if (! empty($item['image_badge']))
                                        <div class="absolute top-3 left-3 bg-surface/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 {{ $r->tone($tone, 'text') }}">
                                            @if (! empty($item['icon']))<x-icon :name="$item['icon']" class="text-[14px]" />@endif{!! $r->t($item['image_badge']) !!}
                                        </div>
                                    @endif
                                </div>
                            @endif
                            <div class="space-y-2">
                                <h3 class="font-headline text-xl font-bold text-on-surface">{!! $r->t($item['title'] ?? '') !!}</h3>
                                @if (! empty($item['text']))<p class="text-sm text-on-surface-variant leading-relaxed">{!! $r->t($item['text']) !!}</p>@endif
                            </div>
                            @if ($lines = $r->lines($item['bullets'] ?? ''))
                                <ul class="space-y-2.5 text-xs sm:text-sm text-on-surface-variant">
                                    @foreach ($lines as $line)
                                        <li class="flex items-start gap-2.5"><x-icon name="check_circle" class="text-[18px] text-primary mt-0.5" /><span>{!! $r->t($line) !!}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        @if (! empty($item['footer_text']) || ! empty($item['link_label']))
                            <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <span class="text-xs text-secondary font-medium">{!! $r->t($item['footer_text'] ?? '') !!}</span>
                                @if (! empty($item['link_label']))
                                    <a {!! $r->linkAttrs($item['link_url'] ?? '') !!} class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:opacity-80">{!! $r->t($item['link_label']) !!}<x-icon name="arrow_forward" class="text-[16px]" /></a>
                                @endif
                            </div>
                        @endif
                    </div>

                @else {{-- vertical --}}
                    <{{ $tag }} @if ($hasLink) {!! $r->linkAttrs($item['link_url']) !!} @endif class="group p-6 sm:p-7 rounded-2xl {{ $cardBg }} shadow-sm hover:shadow-md {{ $hasLink ? 'hover:-translate-y-0.5' : '' }} transition-all flex flex-col justify-between gap-5">
                        <div class="space-y-4">
                            @if (! empty($item['label']) || ! empty($item['icon']))
                                <div class="flex items-center justify-between gap-3">
                                    @if (! empty($item['icon']))
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors {{ $r->tone($tone, 'soft') }} {{ $r->tone($tone, 'hover') }}"><x-icon :name="$item['icon']" class="text-[26px]" /></div>
                                    @endif
                                    @if (! empty($item['label']))
                                        <span class="text-xs font-bold tracking-wider text-tertiary uppercase">{!! $r->t($item['label']) !!}</span>
                                    @endif
                                </div>
                            @endif
                            <div class="space-y-1.5">
                                <h3 class="font-headline text-lg sm:text-xl font-bold text-on-surface">{!! $r->t($item['title'] ?? '') !!}</h3>
                                @if (! empty($item['tag']))<p class="text-xs font-semibold uppercase tracking-wider {{ $r->tone($tone, 'text') }}">{!! $r->t($item['tag']) !!}</p>@endif
                            </div>
                            @if (! empty($item['text']))<p class="text-sm text-on-surface-variant leading-relaxed">{!! $r->t($item['text']) !!}</p>@endif
                            @if ($lines = $r->lines($item['bullets'] ?? ''))
                                <ul class="space-y-2 text-sm text-on-surface-variant">
                                    @foreach ($lines as $line)
                                        <li class="flex items-start gap-2"><x-icon name="check" class="text-[16px] text-primary mt-0.5" /><span>{!! $r->t($line) !!}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        @if (! empty($item['note_text']))
                            <div class="bg-surface-container rounded-xl p-3.5">
                                @if (! empty($item['note_label']))<span class="text-xs font-bold text-on-surface block mb-1">{!! $r->t($item['note_label']) !!}</span>@endif
                                <p class="text-xs text-on-surface-variant leading-snug">{!! $r->t($item['note_text']) !!}</p>
                            </div>
                        @endif
                        @if (! empty($item['footer_text']))
                            <div class="pt-4 border-t border-outline-variant/30 flex items-center gap-2 text-xs font-semibold {{ $r->tone($tone, 'text') }}">
                                <x-icon :name="$item['footer_icon'] ?: 'check_circle'" class="text-[18px]" /><span>{!! $r->t($item['footer_text']) !!}</span>
                            </div>
                        @endif
                        @if (! empty($item['link_label']))
                            <div class="flex items-center gap-1.5 text-xs font-semibold {{ $r->tone($tone, 'text') }}">
                                <span>{!! $r->t($item['link_label']) !!}</span><x-icon name="arrow_forward" class="text-[14px] group-hover:translate-x-1 transition-transform" />
                            </div>
                        @endif
                    </{{ $tag }}>
                @endif
            @endforeach
        </div>

        @if (! empty($d['more_label']))
            <div class="text-center pt-8">
                <a {!! $r->linkAttrs($d['more_url'] ?? '') !!} class="inline-flex items-center gap-2 text-primary font-bold hover:opacity-80 text-sm group">
                    <span>{!! $r->t($d['more_label']) !!}</span><x-icon name="arrow_forward" class="text-[16px] group-hover:translate-x-1 transition-transform" />
                </a>
            </div>
        @endif
    </div>
</section>
