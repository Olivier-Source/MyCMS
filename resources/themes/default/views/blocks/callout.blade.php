@php $alert = ($d['style'] ?? 'soft') === 'alert'; @endphp
<section class="w-full {{ $alert ? 'pb-14 lg:pb-16' : 'py-14 lg:py-16' }}">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-12">
        @if ($alert)
            <div class="rounded-2xl bg-surface-container-low p-6 sm:p-7 border-l-4 border-error shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="space-y-1.5 max-w-3xl">
                    <div class="flex items-center gap-2 text-error font-bold text-sm">
                        <x-icon :name="$d['icon'] ?: 'crisis_alert'" class="text-[20px]" /><span>{!! $r->t($d['title'] ?? '') !!}</span>
                    </div>
                    <div class="prose-cms text-xs sm:text-sm">{!! $r->h($d['text'] ?? '') !!}</div>
                </div>
                @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? [], 'class' => 'flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0 w-full md:w-auto'])
            </div>
        @else
            <div class="bg-surface-container-high rounded-3xl p-6 sm:p-12 lg:p-14 relative overflow-hidden shadow-sm isolate">
                <div class="absolute -right-12 -bottom-12 w-72 h-72 rounded-full bg-primary/5 pointer-events-none -z-10"></div>
                <div class="flex flex-col md:flex-row items-start gap-6 md:gap-8">
                    <div class="shrink-0 w-14 h-14 rounded-2xl bg-primary text-on-primary flex items-center justify-center shadow-sm">
                        <x-icon :name="$d['icon'] ?: 'lightbulb'" class="text-[30px]" />
                    </div>
                    <div class="space-y-4">
                        @if (! empty($d['eyebrow']))<span class="inline-block text-xs font-bold tracking-wider text-tertiary uppercase">{!! $r->t($d['eyebrow']) !!}</span>@endif
                        @if (! empty($d['title']))<h2 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface">{!! $r->t($d['title']) !!}</h2>@endif
                        <div class="prose-cms text-base">{!! $r->h($d['text'] ?? '') !!}</div>
                        @if (! empty($d['footer_text']))
                            <div class="pt-2 flex items-center gap-3">
                                <x-icon :name="$d['footer_icon'] ?: 'check_circle'" class="text-[20px] text-primary" />
                                <span class="text-sm font-semibold text-primary">{!! $r->t($d['footer_text']) !!}</span>
                            </div>
                        @endif
                        @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? []])
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
