@php
    $social = $settings->socialLinks();
    $address = $settings->fullAddress();
@endphp
<footer class="border-t border-outline-variant/40 bg-surface">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-14 grid grid-cols-1 md:grid-cols-3 gap-10">
        <div class="space-y-3">
            <p class="font-headline font-bold text-xl">{{ $settings->siteName() }}</p>
            @if ($settings->get('footer_about'))
                <p class="text-sm text-on-surface-variant leading-relaxed max-w-xs">{!! $ph->text($settings->get('footer_about')) !!}</p>
            @endif
        </div>

        <div class="space-y-2 text-sm">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-on-surface-variant mb-3">{{ __('Contact') }}</p>
            @if ($address)<p>{{ $address }}</p>@endif
            @if ($settings->get('phone'))<p><a href="{{ $settings->phoneHref() }}" class="hover:underline">{{ $settings->get('phone') }}</a></p>@endif
            @if ($settings->get('email'))<p><a href="mailto:{{ $settings->get('email') }}" class="hover:underline break-all">{{ $settings->get('email') }}</a></p>@endif
            @foreach ($settings->hours() as $row)
                <p class="text-on-surface-variant">{{ $row['label'] }}{{ $row['label'] && $row['value'] ? ' : ' : '' }}{{ $row['value'] }}</p>
            @endforeach
        </div>

        <div class="space-y-2 text-sm">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-on-surface-variant mb-3">{{ __('Information') }}</p>
            @foreach ($footerPages as $item)
                <p><a href="{{ $item->url() }}" class="hover:underline">{{ $item->navLabel() }}</a></p>
            @endforeach
            @foreach ($social as $link)
                <p><a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer me" class="hover:underline">{{ $link['label'] }} ↗</a></p>
            @endforeach
        </div>
    </div>

    @if ($settings->get('footer_notice_enabled') && $settings->get('footer_notice_text'))
        <div class="max-w-6xl mx-auto px-5 sm:px-8 pb-6">
            <p class="text-sm border-l-2 border-on-surface pl-4 text-on-surface-variant">{!! $ph->text($settings->get('footer_notice_text')) !!}</p>
        </div>
    @endif

    <div class="border-t border-outline-variant/40">
        <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 flex flex-col sm:flex-row gap-3 justify-between text-xs text-on-surface-variant">
            <p>© {{ now()->year }} {{ $settings->get('company_name') ?: $settings->siteName() }}</p>
            @if ($settings->get('footer_legal_line'))<p>{!! $ph->text($settings->get('footer_legal_line'), false) !!}</p>@endif
            @if ($settings->get('show_credit'))
                <p>{!! $r->sentence('Made with :link', ['link' => '<a href="'.e(config('mycms.project_url')).'" target="_blank" rel="noopener" class="underline">MyCMS</a>']) !!}</p>
            @endif
        </div>
    </div>
</footer>
