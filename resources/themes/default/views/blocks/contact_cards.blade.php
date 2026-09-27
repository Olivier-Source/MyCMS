{{-- Cards filled from "Site information" --}}
@php
    $card = 'p-6 rounded-xl bg-surface-container shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between';
    $hours = $settings->hours();
    $address = $settings->fullAddress();
    $extra = ! empty($d['extra_title']) || ! empty($d['extra_text']);
    $count = ($address ? 1 : 0) + ($hours ? 1 : 0) + 1 + ($extra ? 1 : 0);
@endphp
<section class="px-4 sm:px-6 lg:px-12 py-10">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 {{ $count >= 4 ? 'lg:grid-cols-4' : ($count === 3 ? 'lg:grid-cols-3' : '') }} gap-5">

        @if ($address)
            <div class="{{ $card }}">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-lg bg-primary-fixed flex items-center justify-center text-primary"><x-icon name="location_on" class="text-[22px]" /></div>
                    <h2 class="font-headline text-lg font-semibold text-on-surface">{{ __('Address') }}</h2>
                    <p class="text-sm font-medium text-primary">{{ $address }}</p>
                    @if ($d['address_text'] || $settings->get('address_details'))
                        <p class="text-xs text-on-surface-variant leading-relaxed">{!! $r->t($d['address_text'] ?: $settings->get('address_details')) !!}</p>
                    @endif
                </div>
                @if (! empty($d['address_footer']))<div class="mt-4 pt-3 flex items-center gap-1.5 text-xs text-tertiary font-semibold"><x-icon name="check_circle" class="text-[16px]" /><span>{!! $r->t($d['address_footer']) !!}</span></div>@endif
            </div>
        @endif

        @if ($hours)
            <div class="{{ $card }}">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-lg bg-secondary-fixed flex items-center justify-center text-secondary"><x-icon name="schedule" class="text-[22px]" /></div>
                    <h2 class="font-headline text-lg font-semibold text-on-surface">{{ __('Opening hours') }}</h2>
                    <div class="text-xs space-y-1 text-on-surface-variant leading-relaxed">
                        @foreach ($hours as $row)
                            <p><span class="font-semibold text-on-surface">{{ $row['label'] }}{{ $row['label'] && $row['value'] ? ' :' : '' }}</span> {{ $row['value'] }}</p>
                        @endforeach
                    </div>
                    @if ($settings->get('hours_note'))<p class="text-xs text-on-surface-variant leading-relaxed">{{ $settings->get('hours_note') }}</p>@endif
                </div>
                @if (! empty($d['hours_footer']))<div class="mt-4 pt-3 flex items-center gap-1.5 text-xs text-primary font-semibold"><x-icon name="event_available" class="text-[16px]" /><span>{!! $r->t($d['hours_footer']) !!}</span></div>@endif
            </div>
        @endif

        <div class="{{ $card }}">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-lg bg-tertiary-fixed flex items-center justify-center text-tertiary"><x-icon name="contact_support" class="text-[22px]" /></div>
                <h2 class="font-headline text-lg font-semibold text-on-surface">{{ $settings->get('phone') ? __('Phone & e-mail') : __('E-mail') }}</h2>
                <div class="space-y-1">
                    @if ($settings->get('phone'))
                        <a class="block text-sm font-semibold text-primary hover:underline" href="{{ $settings->phoneHref() }}">{{ $settings->get('phone') }}</a>
                    @endif
                    <a class="block text-xs text-on-surface hover:text-primary transition-colors break-all" href="mailto:{{ $settings->get('email') }}">{{ $settings->get('email') }}</a>
                </div>
                @if (! empty($d['phone_text']))<p class="text-xs text-on-surface-variant leading-relaxed">{!! $r->t($d['phone_text']) !!}</p>@endif
            </div>
            @if (! empty($d['phone_footer']))<div class="mt-4 pt-3 flex items-center gap-1.5 text-xs text-secondary font-semibold"><x-icon name="mark_chat_unread" class="text-[16px]" /><span>{!! $r->t($d['phone_footer']) !!}</span></div>@endif
        </div>

        @if ($extra)
            <div class="{{ $card }}">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-lg bg-primary-fixed flex items-center justify-center text-primary"><x-icon :name="$d['extra_icon'] ?: 'info'" class="text-[22px]" /></div>
                    @if (! empty($d['extra_title']))<h2 class="font-headline text-lg font-semibold text-on-surface">{!! $r->t($d['extra_title']) !!}</h2>@endif
                    @if (! empty($d['extra_text']))<p class="text-xs text-on-surface-variant leading-relaxed">{!! $r->t($d['extra_text']) !!}</p>@endif
                </div>
                @if (! empty($d['extra_footer']))<div class="mt-4 pt-3 flex items-center gap-1.5 text-xs text-primary font-semibold"><x-icon name="verified" class="text-[16px]" /><span>{!! $r->t($d['extra_footer']) !!}</span></div>@endif
            </div>
        @endif
    </div>
</section>
