@php $columns = $d['columns'] ?? []; @endphp
<section class="w-full {{ $r->background($d['background'] ?? 'none') }} py-14 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
        @include('theme::blocks.partials.heading')

        <div class="grid grid-cols-1 {{ count($columns) >= 3 ? 'md:grid-cols-3' : 'lg:grid-cols-2' }} gap-6 lg:gap-8 items-start">
            @foreach ($columns as $col)
                <div class="{{ ($col['highlight'] ?? false) ? 'bg-surface-container-lowest shadow-sm' : 'bg-surface-container-low' }} p-6 sm:p-8 rounded-2xl space-y-5">
                    @if (! empty($col['title']))
                        <div class="flex items-center gap-3">
                            @if (! empty($col['icon']))
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $r->tone($col['tone'] ?? 'primary', 'soft') }}"><x-icon :name="$col['icon']" class="text-[22px]" /></div>
                            @endif
                            <div>
                                <h3 class="font-headline text-lg sm:text-xl font-bold text-on-surface">{!! $r->t($col['title']) !!}</h3>
                                @if (! empty($col['subtitle']))<p class="text-xs font-semibold {{ $r->tone($col['tone'] ?? 'primary', 'text') }}">{!! $r->t($col['subtitle']) !!}</p>@endif
                            </div>
                        </div>
                    @endif
                    <div class="prose-cms text-sm">{!! $r->h($col['text'] ?? '') !!}</div>
                    @if (! empty($col['tiles']))
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($col['tiles'] as $tile)
                                <div class="p-3.5 rounded-xl bg-surface-container-low">
                                    <span class="text-xs text-secondary font-bold uppercase">{!! $r->t($tile['label'] ?? '') !!}</span>
                                    <p class="font-headline font-bold text-lg text-on-surface mt-1">{!! $r->t($tile['value'] ?? '') !!}</p>
                                    <p class="text-xs text-on-surface-variant">{!! $r->t($tile['text'] ?? '') !!}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if (! empty($col['note']))
                        <div class="p-4 rounded-xl bg-secondary-container/50 text-xs text-on-secondary-container flex gap-2">
                            <x-icon name="description" class="text-[18px] text-primary" /><p>{!! $r->t($col['note']) !!}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($chips = $r->lines($d['chips'] ?? ''))
            <div class="mt-8 p-6 rounded-2xl bg-surface-container-low space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-secondary">{!! $r->t($d['chips_title'] ?? '') !!}</span>
                    <span class="text-xs text-on-surface-variant italic">{!! $r->t($d['chips_note'] ?? '') !!}</span>
                </div>
                <div class="flex flex-wrap gap-2.5">
                    @foreach ($chips as $chip)<span class="px-3.5 py-1.5 rounded-lg bg-surface-container-lowest text-xs font-semibold text-on-surface shadow-sm">{!! $r->t($chip) !!}</span>@endforeach
                </div>
            </div>
        @endif
    </div>
</section>
