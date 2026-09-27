@php $items = $d['items'] ?? []; @endphp
<section class="w-full {{ $r->background($d['background'] ?? 'none') }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 py-14 lg:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-start">
            <div class="{{ $items ? 'lg:col-span-6' : 'lg:col-span-8' }} space-y-6">
                @if (! empty($d['eyebrow']))
                    <span class="block text-xs font-bold uppercase tracking-widest text-primary">{!! $r->t($d['eyebrow']) !!}</span>
                @endif
                @if (! empty($d['title']))
                    <h2 class="font-headline text-2xl sm:text-3xl lg:text-4xl font-bold text-on-surface leading-snug">{!! $r->t($d['title']) !!}</h2>
                @endif
                @if (! empty($d['text']))
                    <div class="prose-cms text-base">{!! $r->h($d['text']) !!}</div>
                @endif
                @if (! empty($d['callout_title']) || ! empty($d['callout_text']))
                    <div class="p-5 rounded-2xl bg-secondary-container/70 space-y-3">
                        <div class="flex items-center gap-3 text-on-secondary-fixed">
                            <x-icon :name="$d['callout_icon'] ?: 'lightbulb'" class="text-[24px] text-primary" />
                            <h3 class="font-headline font-semibold text-base">{!! $r->t($d['callout_title'] ?? '') !!}</h3>
                        </div>
                        <p class="text-sm text-on-secondary-fixed-variant leading-relaxed">{!! $r->t($d['callout_text'] ?? '') !!}</p>
                    </div>
                @endif
                @if (! empty($d['chip']))
                    <span class="inline-block text-xs text-secondary font-medium bg-surface px-3 py-1.5 rounded-lg shadow-sm">{!! $r->t($d['chip']) !!}</span>
                @endif
                @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? []])
            </div>

            @if ($items)
                <div class="lg:col-span-6 space-y-4">
                    @if (! empty($d['side_title']))
                        <h3 class="font-headline text-xl font-bold text-on-surface mb-4 lg:mb-6">{!! $r->t($d['side_title']) !!}</h3>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($items as $item)
                            <div class="p-5 rounded-xl {{ ($d['background'] ?? 'none') === 'none' ? 'bg-surface-container-low' : 'bg-surface-container-lowest shadow-sm' }} space-y-2">
                                @if (! empty($item['icon']))
                                    <div class="w-9 h-9 rounded-lg bg-surface-container-highest flex items-center justify-center text-primary"><x-icon :name="$item['icon']" class="text-[20px]" /></div>
                                @endif
                                <h4 class="font-headline text-sm font-bold text-on-surface">{!! $r->t($item['title'] ?? '') !!}</h4>
                                <p class="text-xs text-on-surface-variant leading-relaxed">{!! $r->t($item['text'] ?? '') !!}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
