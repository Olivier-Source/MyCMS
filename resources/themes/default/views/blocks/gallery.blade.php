@php
    $items = array_values(array_filter($d['items'] ?? [], fn ($i) => ! empty($i['image'])));
    $ratio = match ($d['ratio'] ?? 'landscape') { 'square' => 'aspect-square', 'portrait' => 'aspect-[3/4]', default => 'aspect-[4/3]' };
@endphp
<section class="w-full {{ $r->background($d['background'] ?? 'none') }} py-14 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
        @include('theme::blocks.partials.heading')

        @if ($items)
            <ul class="grid grid-cols-1 sm:grid-cols-2 {{ match ((string) ($d['columns'] ?? '3')) { '2' => '', '4' => 'lg:grid-cols-4', default => 'lg:grid-cols-3' } }} gap-4 sm:gap-6">
                @foreach ($items as $item)
                    @php $image = $r->img($item['image']); @endphp
                    @continue(! $image)
                    <li>
                        <figure class="group">
                            @if (! empty($item['url']))<a {!! $r->linkAttrs($item['url']) !!} class="block">@endif
                            <div class="{{ $ratio }} rounded-2xl overflow-hidden bg-surface-container shadow-sm">
                                <img src="{{ $image->url() }}" alt="{{ $image->alt }}" loading="lazy" width="{{ $image->width }}" height="{{ $image->height }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                            </div>
                            @if (! empty($item['url']))</a>@endif
                            @if (! empty($item['caption']))
                                <figcaption class="mt-3 text-sm text-on-surface-variant">{!! $r->t($item['caption']) !!}</figcaption>
                            @endif
                        </figure>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-center text-on-surface-variant">{{ __('No picture yet.') }}</p>
        @endif
    </div>
</section>
