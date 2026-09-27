@php $accordion = ($d['style'] ?? 'accordion') === 'accordion'; $d['align'] = 'center'; @endphp
<section class="w-full {{ $r->background($d['background'] ?? 'soft') }} py-14 lg:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        @include('theme::blocks.partials.heading', ['mb' => 'mb-8 lg:mb-10'])

        <div class="space-y-3.5" @if ($accordion) data-accordion @endif>
            @foreach ($d['items'] ?? [] as $item)
                @if ($accordion)
                    <details class="group bg-surface-container-lowest rounded-2xl shadow-sm overflow-hidden">
                        <summary class="p-5 sm:p-6 flex items-center justify-between gap-4 font-headline font-semibold text-base sm:text-lg text-on-surface hover:text-primary transition-colors">
                            <span>{!! $r->t($item['question'] ?? '') !!}</span>
                            <x-icon name="expand_more" class="chev text-[22px] text-primary" />
                        </summary>
                        <div class="px-5 sm:px-6 pb-6 prose-cms text-sm">{!! $r->h($item['answer'] ?? '') !!}</div>
                    </details>
                @else
                    <div class="rounded-xl bg-surface-container-lowest p-5 shadow-sm space-y-2">
                        <h3 class="font-headline text-base font-semibold text-on-surface flex items-start gap-2.5">
                            <x-icon :name="$item['icon'] ?: 'help'" class="text-[20px] {{ ($item['icon'] ?? '') === 'warning' ? 'text-error' : 'text-primary' }} mt-0.5" />
                            <span>{!! $r->t($item['question'] ?? '') !!}</span>
                        </h3>
                        <div class="prose-cms text-xs sm:text-sm sm:pl-7">{!! $r->h($item['answer'] ?? '') !!}</div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>
