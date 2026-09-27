@php
    $map = $settings->mapEmbedUrl();
    $address = $settings->fullAddress();
    $query = urlencode($address);
    $withForm = $d['show_form'] ?? false;
@endphp
<section class="px-4 sm:px-6 lg:px-12 py-12">
    <div class="max-w-7xl mx-auto grid grid-cols-1 {{ $withForm ? 'lg:grid-cols-12' : '' }} gap-10 items-start">

        <div class="{{ $withForm ? 'lg:col-span-7' : '' }} space-y-8">
            <div class="space-y-4">
                <div>
                    @if (! empty($d['eyebrow']))<span class="text-xs uppercase tracking-wider text-secondary font-semibold">{!! $r->t($d['eyebrow']) !!}</span>@endif
                    @if (! empty($d['title']))<h2 class="font-headline text-2xl font-semibold text-on-surface">{!! $r->t($d['title']) !!}</h2>@endif
                </div>
                <div class="relative rounded-2xl overflow-hidden shadow-md bg-surface-container">
                    @if ($map)
                        <iframe src="{{ $map }}" title="{{ __('Map: :address', ['address' => $address]) }}" class="w-full h-80 block" loading="lazy" referrerpolicy="no-referrer"></iframe>
                    @else
                        <div class="w-full h-80 flex items-center justify-center text-on-surface-variant text-sm px-6 text-center">{{ __('Map coordinates to fill in in "Site information → Contact details".') }}</div>
                    @endif
                    @if ($address)
                        <div class="pointer-events-none absolute bottom-4 left-4 right-4 sm:right-auto bg-surface/95 backdrop-blur-md p-4 rounded-xl shadow-lg flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary text-on-primary flex items-center justify-center shrink-0"><x-icon name="location_on" class="text-[20px]" /></div>
                            <div class="min-w-0 pr-2">
                                <p class="font-headline text-sm font-semibold text-on-surface truncate">{{ $settings->siteName() }}</p>
                                <p class="text-xs text-on-surface-variant truncate">{{ $address }}</p>
                            </div>
                        </div>
                    @endif
                </div>
                @if ($address)
                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        <a href="https://www.openstreetmap.org/search?query={{ $query }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-surface-container text-on-surface text-xs font-semibold hover:bg-surface-container-high transition-colors"><x-icon name="map" class="text-[17px] text-primary" />OpenStreetMap</a>
                        <a href="https://maps.google.com/?q={{ $query }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-surface-container text-on-surface text-xs font-semibold hover:bg-surface-container-high transition-colors"><x-icon name="directions" class="text-[17px] text-primary" />Google Maps</a>
                        <a href="https://maps.apple.com/?address={{ $query }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-surface-container text-on-surface text-xs font-semibold hover:bg-surface-container-high transition-colors"><x-icon name="explore" class="text-[17px] text-primary" />Apple Maps</a>
                    </div>
                @endif
            </div>

            @if (! empty($d['arrival_text']))
                <div class="p-5 rounded-xl bg-surface-container-low flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0 mt-0.5"><x-icon name="key" class="text-[18px]" /></div>
                    <div class="space-y-1">
                        <h3 class="font-headline text-sm font-semibold text-on-surface">{!! $d['arrival_title'] ? $r->t($d['arrival_title']) : e(__('Arrival instructions')) !!}</h3>
                        <p class="text-xs text-on-surface-variant leading-relaxed">{!! $r->t($d['arrival_text']) !!}</p>
                    </div>
                </div>
            @endif

            @if (! empty($d['transports']))
                <div class="space-y-4">
                    <h3 class="font-headline text-lg font-semibold text-on-surface flex items-center gap-2"><x-icon name="commute" class="text-[24px] text-primary" /><span>{!! $d['transports_title'] ? $r->t($d['transports_title']) : e(__('How to get here')) !!}</span></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($d['transports'] as $t)
                            <div class="p-4 rounded-xl bg-surface-container space-y-2">
                                <div class="flex items-center gap-2 text-xs font-bold text-on-surface">
                                    @if (! empty($t['badge']))<span class="min-w-6 h-6 px-1 rounded-full flex items-center justify-center text-[11px] font-bold {{ $r->tone($t['tone'] ?? 'primary', 'solid') }}">{{ $t['badge'] }}</span>@endif
                                    <span>{!! $r->t($t['title'] ?? '') !!}</span>
                                </div>
                                <ul class="text-xs text-on-surface-variant space-y-1.5 leading-relaxed pl-1">
                                    @foreach ($r->lines($t['lines'] ?? '') as $line)<li>{!! $r->t($line) !!}</li>@endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if ($withForm)
            <div class="lg:col-span-5">
                <div class="relative p-6 sm:p-8 rounded-2xl bg-surface-container shadow-md">
                    <div class="space-y-2 mb-6">
                        @if (! empty($d['form_eyebrow']))<span class="text-xs uppercase tracking-wider text-primary font-bold">{!! $r->t($d['form_eyebrow']) !!}</span>@endif
                        @if (! empty($d['form_title']))<h3 class="font-headline text-2xl font-semibold text-on-surface">{!! $r->t($d['form_title']) !!}</h3>@endif
                        @if (! empty($d['form_text']))<p class="text-xs text-on-surface-variant leading-relaxed">{!! $r->t($d['form_text']) !!}</p>@endif
                    </div>
                    @include('theme::blocks.partials.contact-form')
                </div>
            </div>
        @endif
    </div>
</section>
