<section class="w-full {{ $r->background($d['background'] ?? 'soft') }} py-14 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
        @include('theme::blocks.partials.heading')

        @php $count = count($d['items'] ?? []); @endphp
        <div class="grid grid-cols-1 {{ $count >= 3 ? 'md:grid-cols-3' : 'md:grid-cols-2' }} gap-6">
            @foreach ($d['items'] ?? [] as $item)
                @php $tone = $item['tone'] ?? 'primary'; @endphp
                <div class="bg-surface-container-lowest rounded-2xl p-6 sm:p-7 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                    <div class="space-y-5">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                @if (! empty($item['category']))<span class="text-xs font-bold tracking-wide uppercase {{ $r->tone($tone, 'text') }}">{!! $r->t($item['category']) !!}</span>@endif
                                <h3 class="font-headline text-xl font-bold text-on-surface mt-1">{!! $r->t($item['title'] ?? '') !!}</h3>
                            </div>
                            @if (! empty($item['icon']))
                                <div class="p-2 rounded-xl {{ $r->tone($tone, 'soft') }}"><x-icon :name="$item['icon']" class="text-[20px] block" /></div>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-baseline gap-x-2">
                            <span class="text-4xl font-headline font-bold text-on-surface">{!! $r->t($item['price'] ?? '') !!}</span>
                            <span class="text-xs text-secondary">{!! $r->t($item['unit'] ?? '') !!}</span>
                        </div>
                        @if (! empty($item['text']))<p class="text-xs sm:text-sm text-on-surface-variant leading-relaxed">{!! $r->t($item['text']) !!}</p>@endif
                        @if ($lines = $r->lines($item['bullets'] ?? ''))
                            <ul class="space-y-2 text-xs text-on-surface-variant pt-1">
                                @foreach ($lines as $line)
                                    <li class="flex items-center gap-2"><x-icon name="check" class="text-[14px] text-primary" /><span>{!! $r->t($line) !!}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @if (! empty($item['button_label']))
                        <div class="mt-8">
                            <a {!! $r->linkAttrs($item['button_url']) !!} class="w-full py-3 px-4 rounded-xl font-semibold text-sm inline-flex items-center justify-center gap-2 transition-colors {{ ($item['highlight'] ?? true) ? 'bg-btn text-on-btn hover:bg-btn-hover' : 'bg-surface-container-high hover:bg-surface-container-highest text-on-surface' }}">
                                <span>{!! $r->t($item['button_label']) !!}</span><x-icon name="chevron_right" class="text-[16px]" />
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if (! empty($d['infos']))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-8">
                @foreach ($d['infos'] as $info)
                    <div class="bg-surface-container-lowest p-6 rounded-2xl flex items-start gap-4">
                        <div class="p-3 rounded-xl shrink-0 {{ $r->tone($info['tone'] ?? 'primary', 'soft') }}"><x-icon :name="$info['icon'] ?: 'info'" class="text-[24px] block" /></div>
                        <div class="space-y-1.5">
                            <h4 class="font-headline font-bold text-base text-on-surface">{!! $r->t($info['title'] ?? '') !!}</h4>
                            <div class="prose-cms text-xs sm:text-sm">{!! $r->h($info['text'] ?? '') !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
