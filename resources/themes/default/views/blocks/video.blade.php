{{-- The video player is only loaded when the visitor clicks (privacy, speed) --}}
@php
    $embed = $r->videoEmbed($d['url'] ?? '');
    $poster = $r->img($d['poster'] ?? null);
@endphp
<section class="w-full {{ $r->background($d['background'] ?? 'none') }} py-14 lg:py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-12">
        @include('theme::blocks.partials.heading')

        @if ($embed)
            <figure>
                <div class="relative aspect-video rounded-2xl overflow-hidden bg-on-surface shadow-lg">
                    <button type="button" data-video="{{ $embed }}" data-title="{{ $r->raw($d['title'] ?? '') }}"
                            class="group absolute inset-0 w-full h-full flex items-center justify-center" aria-label="{{ __('Play the video') }}">
                        @if ($poster)
                            <img src="{{ $poster->url() }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:opacity-100 transition-opacity">
                        @endif
                        <span class="relative w-20 h-20 rounded-full bg-btn text-on-btn flex items-center justify-center shadow-xl group-hover:scale-105 transition-transform">
                            <x-icon name="play_circle" class="text-[48px]" />
                        </span>
                        <span class="absolute bottom-3 left-3 right-3 text-[11px] text-white/80 text-center">{{ __('Clicking loads the player of :provider.', ['provider' => str_contains($embed, 'vimeo') ? 'Vimeo' : 'YouTube']) }}</span>
                    </button>
                </div>
                @if (! empty($d['caption']))
                    <figcaption class="mt-3 text-sm text-center text-on-surface-variant">{!! $r->t($d['caption']) !!}</figcaption>
                @endif
            </figure>
        @else
            <p class="text-center text-on-surface-variant">{{ __('Add the address of a YouTube or Vimeo video.') }}</p>
        @endif
    </div>
</section>
