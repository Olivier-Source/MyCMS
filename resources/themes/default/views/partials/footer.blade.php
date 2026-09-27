@php
    $logo = $r->setting('logo_id');
    $hours = $settings->hours();
    $social = $settings->socialLinks();
    $address = $settings->fullAddress();
    $legalIds = array_filter([
        __('Company') => $settings->get('company_name') !== $settings->siteName() ? $settings->get('company_name') : null,
        __('Registration no.') => $settings->get('registration_number'),
        __('VAT no.') => $settings->get('vat_number'),
    ]);
@endphp
<footer class="w-full bg-surface-container pt-14 pb-10 sm:py-16 text-on-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
        @if ($settings->get('footer_notice_enabled') && $settings->get('footer_notice_text'))
            <div class="mb-10 p-4 rounded-xl bg-surface-container-high flex items-start gap-3 text-secondary">
                <x-icon name="info" class="text-[22px] text-primary mt-0.5" />
                <p class="text-sm font-medium">{!! $ph->text($settings->get('footer_notice_text')) !!}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
            <div class="space-y-4">
                <a href="{{ $homeUrl }}" class="flex items-center gap-3">
                    @if ($logo)
                        <img src="{{ $logo->url() }}" alt="" class="h-9 w-auto max-w-[8rem] object-contain">
                    @else
                        <span class="w-9 h-9 shrink-0 rounded-full bg-primary text-on-primary font-headline font-bold text-xs flex items-center justify-center" aria-hidden="true">{{ $settings->get('initials') }}</span>
                    @endif
                    <span class="font-headline font-bold text-lg text-on-surface">{{ $settings->siteName() }}</span>
                </a>
                @if ($settings->get('footer_about'))
                    <p class="text-sm text-on-surface-variant leading-relaxed">{!! $ph->text($settings->get('footer_about')) !!}</p>
                @endif
                @if ($legalIds)
                    <div class="pt-2 space-y-1 text-xs text-on-surface-variant">
                        @foreach ($legalIds as $label => $value)
                            <p><span class="font-semibold text-on-surface">{{ $label }} :</span> {{ $value }}</p>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                <h2 class="font-headline text-base font-semibold text-on-surface">{{ __('Contact') }}</h2>
                <div class="space-y-2 text-sm text-on-surface-variant">
                    @if ($address)
                        <p class="flex items-start gap-2"><x-icon name="location_on" class="text-[18px] text-primary mt-0.5" /><span>{{ $address }}@if ($settings->get('address_details'))<br><span class="text-xs text-secondary">{{ $settings->get('address_details') }}</span>@endif</span></p>
                    @endif
                    @if ($hours)
                        <div class="flex items-start gap-2 pt-1"><x-icon name="schedule" class="text-[18px] text-primary mt-0.5" />
                            <ul>@foreach ($hours as $row)<li>{{ $row['label'] }}{{ $row['label'] && $row['value'] ? ' : ' : '' }}{{ $row['value'] }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @if ($settings->get('phone'))
                        <a href="{{ $settings->phoneHref() }}" class="flex items-center gap-2 pt-1 hover:text-primary transition-colors"><x-icon name="call" class="text-[18px] text-primary" />{{ $settings->get('phone') }}</a>
                    @endif
                    @if ($settings->get('email'))
                        <a href="mailto:{{ $settings->get('email') }}" class="flex items-center gap-2 hover:text-primary transition-colors break-all"><x-icon name="mail" class="text-[18px] text-primary" />{{ $settings->get('email') }}</a>
                    @endif
                </div>
            </div>

            <div class="space-y-3">
                <h2 class="font-headline text-base font-semibold text-on-surface">{{ __('Site map') }}</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($navPages as $item)
                        <li><a href="{{ $item->url() }}" class="text-on-surface-variant hover:text-primary transition-colors">{{ $item->navLabel() }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="space-y-3">
                @if ($footerPages->isNotEmpty())
                    <h2 class="font-headline text-base font-semibold text-on-surface">{{ __('Information') }}</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($footerPages as $item)
                            <li><a href="{{ $item->url() }}" class="text-on-surface-variant hover:text-primary transition-colors">{{ $item->navLabel() }}</a></li>
                        @endforeach
                    </ul>
                @endif
                @if ($social)
                    <h2 class="font-headline text-base font-semibold text-on-surface {{ $footerPages->isNotEmpty() ? 'pt-3' : '' }}">{{ __('Follow us') }}</h2>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($social as $link)
                            <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer me" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-container-high text-xs font-semibold text-on-surface hover:bg-primary hover:text-on-primary transition-colors"><x-icon name="open_in_new" class="text-[14px]" />{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-outline-variant/30 flex flex-col md:flex-row items-center justify-between text-xs text-on-surface-variant gap-4 text-center md:text-left">
            <p>© {{ now()->year }} {{ $settings->get('company_name') ?: $settings->siteName() }} • {{ __('All rights reserved.') }}</p>
            @if ($settings->get('footer_legal_line'))
                <p>{!! $ph->text($settings->get('footer_legal_line'), false) !!}</p>
            @endif
            @if ($settings->get('show_credit'))
                <p>{!! $r->sentence('Made with :link', ['link' => '<a href="'.e(config('mycms.project_url')).'" target="_blank" rel="noopener" class="font-semibold text-on-surface hover:text-primary transition-colors">MyCMS</a>']) !!}</p>
            @endif
        </div>
    </div>
</footer>
