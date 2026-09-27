{{-- Boutons d'un bloc. Paramètres : $buttons, $class (conteneur), $full (pleine largeur sur mobile) --}}
@php $buttons = array_filter($buttons ?? [], fn ($b) => ! empty($b['label'])); @endphp
@if ($buttons)
    <div class="{{ $class ?? 'flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3 sm:gap-4' }}">
        @foreach ($buttons as $btn)
            @php $primary = ($btn['style'] ?? 'primary') === 'primary'; @endphp
            <a {!! $r->linkAttrs($btn['url'] ?? '') !!}
               class="inline-flex items-center justify-center text-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-sm sm:text-base transition-all {{ $primary ? 'bg-btn text-on-btn hover:bg-btn-hover shadow-md hover:shadow-lg' : 'bg-surface-container-high text-on-surface hover:bg-surface-variant' }}">
                @if (! empty($btn['icon']))
                    <x-icon :name="$btn['icon']" class="text-[20px] {{ $primary ? '' : 'text-primary' }}" />
                @endif
                <span>{!! $r->t($btn['label']) !!}</span>
            </a>
        @endforeach
    </div>
@endif
