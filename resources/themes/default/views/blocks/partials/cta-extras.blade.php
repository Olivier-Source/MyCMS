{{-- Contact details, opening hours and secondary links under a call to action --}}
@php $justify = ($align ?? 'center') === 'left' ? 'sm:justify-start' : 'sm:justify-center'; @endphp

@if (($d['show_contact'] ?? false) && ($settings->get('phone') || $settings->fullAddress()))
    <div class="pt-4 flex flex-col sm:flex-row sm:flex-wrap sm:items-center {{ $justify }} gap-x-6 gap-y-3 text-sm text-on-surface-variant font-medium">
        @if ($settings->get('phone'))
            <div class="flex items-center gap-2"><x-icon name="call" class="text-[16px] text-primary" /><a href="{{ $settings->phoneHref() }}" class="hover:text-primary transition-colors font-bold text-on-surface">{{ $settings->get('phone') }}</a></div>
        @endif
        @if ($settings->fullAddress())
            <div class="flex items-center gap-2"><x-icon name="location_on" class="text-[16px] text-primary" /><span>{{ $settings->fullAddress() }}</span></div>
        @endif
    </div>
@endif

@if ($d['show_hours'] ?? false)
    <div class="pt-2 flex flex-wrap items-center {{ $justify }} gap-x-6 gap-y-2 text-xs text-on-surface-variant">
        @foreach ($settings->hours() as $row)
            <span class="flex items-center gap-1.5"><x-icon name="schedule" class="text-[16px] text-primary" /><span>{{ $row['label'] }}{{ $row['label'] && $row['value'] ? ' :' : '' }} <strong>{{ $row['value'] }}</strong></span></span>
        @endforeach
    </div>
@endif

@php $links = array_filter($d['links'] ?? [], fn ($l) => ! empty($l['label'])); @endphp
@if ($links)
    <div class="pt-4 flex flex-wrap items-center {{ $justify }} justify-center gap-6 text-sm text-secondary font-medium">
        @foreach ($links as $link)
            <a {!! $r->linkAttrs($link['url'] ?? '') !!} class="hover:text-primary transition-colors flex items-center gap-1.5 underline underline-offset-4 decoration-primary/40">
                @if (! empty($link['icon']))<x-icon :name="$link['icon']" class="text-[16px]" />@endif<span>{!! $r->t($link['label']) !!}</span>
            </a>
        @endforeach
    </div>
@endif
