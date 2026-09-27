<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 pb-14 lg:pb-20">
    <div class="p-6 sm:p-8 rounded-2xl bg-surface-container-high grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-center">
        <div class="lg:col-span-4 space-y-2">
            @if (! empty($d['eyebrow']))<span class="text-xs font-bold uppercase tracking-wider text-primary">{!! $r->t($d['eyebrow']) !!}</span>@endif
            <div class="flex flex-wrap items-baseline gap-x-2">
                <span class="font-headline text-4xl sm:text-5xl font-bold text-on-surface">{!! $r->t($d['price'] ?? '') !!}</span>
                <span class="text-sm text-on-surface-variant">{!! $r->t($d['unit'] ?? '') !!}</span>
            </div>
            @if (! empty($d['note']))<p class="text-xs text-on-surface-variant">{!! $r->t($d['note']) !!}</p>@endif
            @if (! empty($d['link_label']))
                <a {!! $r->linkAttrs($d['link_url']) !!} class="inline-flex items-center gap-1 pt-1 text-sm font-bold text-primary hover:opacity-80 group">{!! $r->t($d['link_label']) !!}<x-icon name="arrow_forward" class="text-[16px] group-hover:translate-x-1 transition-transform" /></a>
            @endif
        </div>
        <div class="lg:col-span-8 space-y-3">
            @if (! empty($d['title']))
                <h3 class="font-headline font-bold text-base text-on-surface flex items-center gap-2">
                    <x-icon :name="$d['icon'] ?: 'account_balance_wallet'" class="text-[20px] text-primary" /><span>{!! $r->t($d['title']) !!}</span>
                </h3>
            @endif
            <div class="prose-cms text-sm">{!! $r->h($d['text'] ?? '') !!}</div>
            @if (! empty($d['link2_label']))
                <a {!! $r->linkAttrs($d['link2_url']) !!} class="inline-flex items-center gap-1 text-sm font-bold text-primary hover:opacity-80 group">{!! $r->t($d['link2_label']) !!}<x-icon name="arrow_forward" class="text-[16px] group-hover:translate-x-1 transition-transform" /></a>
            @endif
        </div>
    </div>
</section>
