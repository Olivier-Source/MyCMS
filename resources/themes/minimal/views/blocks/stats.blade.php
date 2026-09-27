{{-- "Key figures" section, declared in theme.json → "blocks" → "stats" --}}
@php
    $items = array_filter($d['items'] ?? [], fn ($i) => ($i['value'] ?? '') !== '');
    $dark = $d['dark'] ?? false;
@endphp
<section class="{{ $dark ? 'bg-on-surface text-surface' : 'bg-surface-container-low text-on-surface' }} py-16 sm:py-24">
    <div class="max-w-6xl mx-auto px-5 sm:px-8">
        @if (! empty($d['title']) || ! empty($d['intro']))
            <div class="max-w-2xl mb-12">
                @if (! empty($d['title']))<h2 class="font-headline text-3xl sm:text-4xl font-extrabold tracking-tight">{!! $r->t($d['title']) !!}</h2>@endif
                @if (! empty($d['intro']))<p class="mt-4 {{ $dark ? 'opacity-80' : 'text-on-surface-variant' }} leading-relaxed">{!! $r->t($d['intro']) !!}</p>@endif
            </div>
        @endif
        <dl class="grid grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach ($items as $item)
                <div class="flex flex-col border-t-2 {{ $dark ? 'border-surface/30' : 'border-on-surface' }} pt-5">
                    <dt class="order-2 text-sm {{ $dark ? 'opacity-80' : 'text-on-surface-variant' }}">{!! $r->t($item['label'] ?? '') !!}</dt>
                    <dd class="font-headline text-4xl sm:text-5xl font-extrabold tracking-tight mb-2">{!! $r->t($item['value']) !!}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
