<section class="py-14 md:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 w-full">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">
        <div class="lg:col-span-4 lg:sticky lg:top-28 space-y-6">
            @if (! empty($d['eyebrow']))<span class="block text-xs font-bold uppercase tracking-wider text-primary">{!! $r->t($d['eyebrow']) !!}</span>@endif
            @if (! empty($d['title']))<h2 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface leading-tight">{!! $r->t($d['title']) !!}</h2>@endif
            @if (! empty($d['text']))<p class="text-on-surface-variant text-base leading-relaxed">{!! $r->t($d['text']) !!}</p>@endif

            @if (! empty($d['stats']))
                <div class="p-5 rounded-2xl bg-surface-container space-y-4">
                    @foreach ($d['stats'] as $stat)
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shrink-0 {{ $r->tone($stat['tone'] ?? 'primary', 'solid') }}">{!! $r->t($stat['value'] ?? '') !!}</div>
                            <div>
                                <p class="text-sm font-bold text-on-surface">{!! $r->t($stat['label'] ?? '') !!}</p>
                                <p class="text-xs text-on-surface-variant">{!! $r->t($stat['text'] ?? '') !!}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (! empty($d['quote']))
                <p class="text-xs text-secondary italic border-l-2 border-primary/40 pl-3">{!! $r->quote($d['quote']) !!}</p>
            @endif
        </div>

        <div class="lg:col-span-8 space-y-5">
            @foreach ($d['items'] ?? [] as $item)
                @if ($item['highlight'] ?? false)
                    <div class="p-6 md:p-8 rounded-2xl bg-secondary-container/60 shadow-sm">
                        <div class="flex items-center gap-2 text-xs font-bold text-tertiary mb-3">
                            <x-icon :name="$item['icon'] ?: 'school'" class="text-[16px]" /><span>{!! $r->t(trim(($item['period'] ?? '').' • '.($item['place'] ?? ''), ' •')) !!}</span>
                        </div>
                        <h3 class="font-headline text-lg sm:text-xl font-bold text-on-surface mb-1">{!! $r->t($item['title'] ?? '') !!}</h3>
                        @if (! empty($item['subtitle']))<p class="text-xs font-semibold text-tertiary mb-3">{!! $r->t($item['subtitle']) !!}</p>@endif
                        <p class="text-sm text-on-surface-variant leading-relaxed">{!! $r->t($item['text'] ?? '') !!}</p>
                    </div>
                @else
                    <div class="p-6 md:p-8 rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            @if (! empty($item['period']))
                                <div class="inline-flex items-center gap-2 text-xs font-bold {{ $loop->first ? 'text-primary bg-primary/10' : 'text-secondary bg-surface-container-highest' }} px-3 py-1 rounded-full w-fit">
                                    <x-icon :name="$item['icon'] ?: 'event_available'" class="text-[14px]" /><span>{!! $r->t($item['period']) !!}</span>
                                </div>
                            @endif
                            @if (! empty($item['place']))<span class="text-xs text-secondary font-semibold">{!! $r->t($item['place']) !!}</span>@endif
                        </div>
                        <h3 class="font-headline text-lg sm:text-xl font-bold text-on-surface mb-2">{!! $r->t($item['title'] ?? '') !!}</h3>
                        @if (! empty($item['subtitle']))<p class="text-xs font-semibold text-tertiary mb-2">{!! $r->t($item['subtitle']) !!}</p>@endif
                        <p class="text-sm text-on-surface-variant leading-relaxed">{!! $r->t($item['text'] ?? '') !!}</p>
                        @if ($tags = $r->tags($item['tags'] ?? ''))
                            <div class="flex flex-wrap gap-2 text-xs mt-4">
                                @foreach ($tags as $tag)<span class="px-2.5 py-1 rounded-md bg-surface text-on-surface">{{ $tag }}</span>@endforeach
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach

            @if (! empty($d['extra_items']))
                <div class="p-6 rounded-2xl bg-surface-container space-y-4">
                    <div class="flex items-center gap-2.5">
                        <x-icon :name="$d['extra_icon'] ?: 'auto_stories'" class="text-[24px] text-primary" />
                        <h4 class="font-headline font-bold text-base text-on-surface">{!! $r->t($d['extra_title'] ?? '') !!}</h4>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        @foreach ($d['extra_items'] as $extra)
                            <div class="p-3.5 rounded-xl bg-surface text-xs space-y-1">
                                <p class="font-bold text-on-surface">{!! $r->t($extra['title'] ?? '') !!}</p>
                                <p class="text-on-surface-variant">{!! $r->t($extra['text'] ?? '') !!}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
