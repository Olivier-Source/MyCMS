@php $style = $d['style'] ?? 'banner'; @endphp

@if ($style === 'centered')
    <section class="py-14 md:py-24 bg-surface-container-high relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center space-y-8">
            @if (! empty($d['icon']))
                <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto"><x-icon :name="$d['icon']" class="text-[30px]" /></div>
            @endif
            <div class="space-y-3">
                @if (! empty($d['badge']))<span class="text-xs font-bold uppercase tracking-widest text-primary">{!! $r->t($d['badge']) !!}</span>@endif
                <h2 class="font-headline text-3xl sm:text-4xl font-bold text-on-surface leading-tight">{!! $r->t($d['title'] ?? '') !!}</h2>
                @if (! empty($d['text']))<p class="text-base sm:text-lg text-on-surface-variant max-w-2xl mx-auto leading-relaxed">{!! $r->t($d['text']) !!}</p>@endif
            </div>
            @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? [], 'class' => 'flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-4 pt-2'])
            @include('theme::blocks.partials.cta-extras')
        </div>
    </section>

@elseif ($style === 'split')
    <section class="w-full py-14 md:py-20 bg-surface-container-high">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-12">
            <div class="bg-surface rounded-3xl p-6 sm:p-12 lg:p-16 shadow-md flex flex-col lg:flex-row items-center justify-between gap-10">
                <div class="max-w-xl space-y-4 text-center lg:text-left">
                    @if (! empty($d['badge']))
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold">
                            <x-icon :name="$d['icon'] ?: 'handshake'" class="text-[16px]" /><span>{!! $r->t($d['badge']) !!}</span>
                        </div>
                    @endif
                    <h2 class="font-headline text-3xl sm:text-4xl font-bold text-on-surface">{!! $r->t($d['title'] ?? '') !!}</h2>
                    @if (! empty($d['text']))<p class="text-base text-on-surface-variant leading-relaxed">{!! $r->t($d['text']) !!}</p>@endif
                    @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? [], 'class' => 'pt-2 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start'])
                </div>
                @if (! empty($d['side_items']) || ! empty($d['side_title']))
                    <div class="w-full lg:w-auto shrink-0 bg-surface-container-low p-6 sm:p-8 rounded-2xl space-y-4 lg:max-w-sm">
                        <h3 class="font-headline text-lg font-bold text-on-surface flex items-center gap-2"><x-icon name="info" class="text-[20px] text-primary" /><span>{!! $r->t($d['side_title'] ?? '') !!}</span></h3>
                        <div class="space-y-3 text-xs sm:text-sm text-secondary">
                            @foreach ($d['side_items'] ?? [] as $row)
                                <div class="flex items-start gap-2.5"><x-icon :name="$row['icon'] ?: 'check'" class="text-[18px] text-primary mt-0.5" /><span>{!! $r->t($row['text'] ?? '') !!}</span></div>
                            @endforeach
                        </div>
                        @if (! empty($d['side_link_label']))
                            <div class="pt-2 border-t border-outline-variant/30 text-center">
                                <a {!! $r->linkAttrs($d['side_link_url']) !!} class="text-xs text-primary font-semibold hover:underline inline-flex items-center justify-center gap-1">{!! $r->t($d['side_link_label']) !!}<x-icon name="chevron_right" class="text-[14px]" /></a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

@else {{-- banner --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 py-14 lg:py-20">
        <div class="p-6 sm:p-12 lg:p-16 rounded-3xl bg-primary-fixed/40 text-on-surface relative overflow-hidden isolate shadow-sm">
            <div class="relative z-10 max-w-3xl space-y-6">
                @if (! empty($d['badge']))
                    <span class="inline-block px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider">{!! $r->t($d['badge']) !!}</span>
                @endif
                <h2 class="font-headline text-3xl sm:text-4xl lg:text-5xl font-bold text-on-surface leading-tight">{!! $r->t($d['title'] ?? '') !!}</h2>
                @if (! empty($d['text']))<p class="text-base sm:text-lg text-on-surface-variant leading-relaxed">{!! $r->t($d['text']) !!}</p>@endif
                @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? [], 'class' => 'pt-2 flex flex-col sm:flex-row gap-3'])
                @include('theme::blocks.partials.cta-extras', ['align' => 'left'])
            </div>
            <div class="absolute -right-16 -bottom-16 w-80 h-80 rounded-full bg-primary/10 pointer-events-none blur-2xl -z-10"></div>
        </div>
    </section>
@endif
