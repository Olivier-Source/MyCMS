@php
    $layout = $d['layout'] ?? 'card';
    $image = $r->img($d['image'] ?? null);
    $withMedia = $layout !== 'simple';
    $badges = array_filter($d['badges'] ?? [], fn ($b) => ! empty($b['text']));
    $captionIcon = $d['image_icon'] ?: 'star';
@endphp
<div class="relative w-full overflow-hidden isolate">
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[720px] max-w-[200%] h-[340px] bg-primary-fixed/30 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 {{ $page?->is_home ? 'py-10 sm:py-12 lg:py-20' : 'pt-8 pb-12 sm:py-12 lg:py-16' }}">
        @if ($d['show_breadcrumb'] ?? false)
            @include('theme::blocks.partials.breadcrumb', ['class' => 'mb-8'])
        @endif

        <div class="grid grid-cols-1 {{ $withMedia ? 'lg:grid-cols-12' : '' }} gap-10 lg:gap-16 items-center">
            <div class="{{ $withMedia ? 'lg:col-span-7' : 'max-w-3xl' }} space-y-6">
                @if (! empty($d['badge']))
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-secondary-container text-on-secondary-fixed text-xs font-semibold tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        <span>{!! $r->t($d['badge']) !!}</span>
                    </div>
                @endif

                <h1 class="font-headline text-3xl sm:text-4xl lg:text-5xl font-bold text-on-surface leading-[1.2] tracking-tight">
                    {!! $r->t($d['title'] ?? '') !!}
                    @if (! empty($d['subtitle']))
                        <span class="block text-primary italic font-normal text-xl sm:text-3xl lg:text-4xl mt-2">{!! $r->t($d['subtitle']) !!}</span>
                    @endif
                </h1>

                @if (! empty($d['text']))
                    <p class="text-on-surface-variant text-base sm:text-lg leading-relaxed max-w-2xl">{!! $r->t($d['text']) !!}</p>
                @endif

                @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? [], 'class' => 'flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3 sm:gap-4 pt-2'])

                @if ($badges)
                    <div class="pt-4 sm:pt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach ($badges as $badge)
                            <div class="p-3 rounded-lg bg-surface-container-low flex items-start gap-2.5">
                                <x-icon :name="$badge['icon'] ?: 'check_circle'" class="text-[20px] text-primary mt-0.5" />
                                <span class="text-xs leading-snug">
                                    @if (! empty($badge['title']))<span class="block font-bold text-sm text-on-surface">{!! $r->t($badge['title']) !!}</span>@endif
                                    <span class="{{ ! empty($badge['title']) ? 'text-secondary' : 'font-medium text-on-surface' }}">{!! $r->t($badge['text']) !!}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (! empty($d['help_text']) && $settings->get('phone'))
                    <div class="px-4 py-3 rounded-lg bg-surface-container-high/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5 text-xs text-on-surface-variant">
                        <span class="flex items-center gap-2"><x-icon name="help" class="text-[16px] text-tertiary" />{!! $r->t($d['help_text']) !!}</span>
                        <a href="{{ $settings->phoneHref() }}" class="font-bold text-primary hover:opacity-80 flex items-center gap-1.5"><x-icon name="phone_in_talk" class="text-[14px]" />{{ __('Call us: :phone', ['phone' => $settings->get('phone')]) }}</a>
                    </div>
                @endif
            </div>

            @if ($withMedia)
                <div class="lg:col-span-5 relative">
                    @if ($layout === 'card')
                        <div class="relative rounded-2xl bg-surface-container-lowest p-4 sm:p-7 shadow-lg max-w-md mx-auto lg:max-w-none">
                            <div class="relative w-full aspect-square rounded-xl overflow-hidden {{ ! empty($d['quote']) ? 'mb-5' : '' }} bg-surface-container-low">
                                @if ($image)
                                    <img src="{{ $image->url() }}" alt="{{ $image->alt }}" class="w-full h-full object-cover" width="{{ $image->width }}" height="{{ $image->height }}">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-primary/40"><x-icon name="image" class="text-[96px]" /></div>
                                @endif
                                @if (! empty($d['image_title']))
                                    <div class="absolute bottom-3 left-3 bg-surface/90 backdrop-blur-md px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-sm">
                                        <x-icon :name="$captionIcon" class="text-[16px] text-primary" /><span class="text-xs font-semibold text-on-surface">{!! $r->t($d['image_title']) !!}</span>
                                    </div>
                                @endif
                            </div>
                            @if (! empty($d['quote']))
                                <div class="p-4 rounded-xl bg-surface-container-low space-y-2">
                                    <x-icon name="format_quote" class="text-[28px] text-tertiary-container" />
                                    <p class="font-headline italic text-on-surface text-sm sm:text-base leading-relaxed">{!! $r->quote($d['quote']) !!}</p>
                                    <div class="flex flex-wrap items-center justify-between gap-1 pt-1">
                                        <span class="text-xs font-bold text-on-surface uppercase tracking-wider">{!! $r->t($d['quote_author'] ?? '') !!}</span>
                                        <span class="text-xs text-on-surface-variant">{!! $r->t($d['quote_role'] ?? '') !!}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                    @elseif ($layout === 'portrait')
                        <div class="relative w-full max-w-sm sm:max-w-md mx-auto">
                            <div class="absolute -inset-3 bg-secondary-container rounded-2xl rotate-2 -z-10"></div>
                            <div class="absolute -inset-1 bg-surface-container-high rounded-2xl -rotate-1 -z-10"></div>
                            <div class="relative rounded-2xl overflow-hidden shadow-xl bg-surface-container-low aspect-square">
                                @if ($image)
                                    <img src="{{ $image->url() }}" alt="{{ $image->alt }}" class="w-full h-full object-cover" width="{{ $image->width }}" height="{{ $image->height }}">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-primary/40"><x-icon name="person" class="text-[96px]" /></div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-on-surface/50 via-transparent to-transparent"></div>
                                @if (! empty($d['image_title']))
                                    <div class="absolute bottom-4 left-4 right-4 p-3.5 rounded-xl bg-surface/95 backdrop-blur-md shadow-sm flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-headline font-semibold text-sm text-on-surface">{!! $r->t($d['image_title']) !!}</p>
                                            @if (! empty($d['image_text']))<p class="text-xs text-on-surface-variant">{!! $r->t($d['image_text']) !!}</p>@endif
                                        </div>
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-fixed text-on-primary-fixed-variant shrink-0"><x-icon :name="$captionIcon" class="text-[18px]" /></span>
                                    </div>
                                @endif
                            </div>
                            @if (! empty($d['floating_text']))
                                <div class="absolute -bottom-6 -left-4 sm:-left-6 p-4 rounded-xl bg-surface-container-lowest shadow-lg max-w-[210px] hidden sm:flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-primary"><x-icon name="auto_awesome" class="text-[20px]" /></span>
                                    <p class="text-xs font-semibold text-on-surface leading-tight">{!! $r->t($d['floating_text']) !!}</p>
                                </div>
                            @endif
                        </div>

                    @else {{-- landscape --}}
                        <div class="relative rounded-2xl overflow-hidden shadow-xl bg-surface-container-high aspect-[4/3] sm:aspect-[16/11]">
                            @if ($image)
                                <img src="{{ $image->url() }}" alt="{{ $image->alt }}" class="w-full h-full object-cover" width="{{ $image->width }}" height="{{ $image->height }}">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-primary/40"><x-icon name="image" class="text-[96px]" /></div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-on-surface/40 via-transparent to-transparent"></div>
                            @if (! empty($d['image_title']) || ! empty($d['image_text']))
                                <div class="absolute bottom-4 left-4 right-4 bg-surface/90 backdrop-blur-md p-4 rounded-xl shadow-md">
                                    <p class="font-headline text-sm font-semibold text-on-surface">{!! $r->t($d['image_title'] ?? '') !!}</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">{!! $r->t($d['image_text'] ?? '') !!}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>
