@extends('admin.layout', ['title' => __('Colours & style')])

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Colours & style') }}</h1>
            <p class="mt-1 text-stone-500">{{ __('Choose a ready-made palette or your own colours: the preview updates live. Nothing changes on the site until you save.') }}</p>
        </div>
        <a href="{{ route('admin.themes.index') }}" class="ck-btn-secondary shrink-0"><x-icon name="extension" class="text-[18px]" />{{ __('Theme: :name', ['name' => $theme->name()]) }}</a>
    </div>

    <form method="POST" action="{{ route('admin.appearance.update') }}" x-data="appearance(@js($colors), @js($presets), @js($default))">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 items-start">
            <div class="xl:col-span-2 space-y-6">
                <section class="ck-card p-5">
                    <h2 class="font-bold mb-3">{{ __('Ready-made palettes') }}</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="(preset, key) in presets" :key="key">
                            <button type="button" @click="usePreset(key)" class="flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left text-sm font-semibold transition"
                                    :class="isPreset(key) ? 'border-emerald-600 bg-emerald-50 ring-4 ring-emerald-600/10' : 'border-stone-200 hover:border-stone-400'">
                                <span class="flex -space-x-1.5 shrink-0">
                                    <span class="w-5 h-5 rounded-full ring-2 ring-white" :style="'background:' + preset.colors.primary"></span>
                                    <span class="w-5 h-5 rounded-full ring-2 ring-white" :style="'background:' + preset.colors.accent"></span>
                                    <span class="w-5 h-5 rounded-full ring-2 ring-white border border-stone-200" :style="'background:' + preset.colors.background"></span>
                                </span>
                                <span x-text="preset.name"></span>
                            </button>
                        </template>
                    </div>
                </section>

                <section class="ck-card p-5">
                    <h2 class="font-bold mb-1">{{ __('Customise') }}</h2>
                    <p class="ck-help mt-0 mb-2">{{ __('Click on a swatch to choose a colour, or type its code.') }}</p>
                    <div class="divide-y divide-stone-100">
                        @foreach ($fields as $key => [$label, $hint])
                            <div class="flex items-center gap-3 py-3">
                                <label class="relative w-11 h-11 shrink-0 rounded-xl ring-1 ring-stone-300 overflow-hidden cursor-pointer" :style="'background:' + colors.{{ $key }}">
                                    <input type="color" x-model="colors.{{ $key }}" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" aria-label="{{ $label }}">
                                </label>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold leading-tight">{{ $label }}</p>
                                    <p class="text-xs text-stone-500 truncate">{{ $hint }}</p>
                                </div>
                                <input type="text" :value="colors.{{ $key }}" @change="setHex('{{ $key }}', $event.target.value); $event.target.value = colors.{{ $key }}"
                                       maxlength="7" spellcheck="false" class="w-24 shrink-0 rounded-lg border border-stone-300 px-2 py-1.5 font-mono text-xs uppercase focus:outline-none focus:ring-2 focus:ring-emerald-600" aria-label="{{ __('Colour code: :name', ['name' => $label]) }}">
                                <input type="hidden" name="colors[{{ $key }}]" :value="colors.{{ $key }}">
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900" x-show="readability.length" x-cloak>
                    <p class="font-semibold flex items-center gap-2"><x-icon name="warning" class="text-[18px]" />{{ __('Readability to check') }}</p>
                    <p class="mt-1">{{ __('The contrast is low for:') }} <span x-text="readability.join(', ')"></span>. {{ __('Some people (visually impaired, on a phone in full sun) will have trouble reading.') }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="ck-btn-primary"><x-icon name="save" class="text-[18px]" />{{ __('Save these colours') }}</button>
                    <button type="button" class="ck-btn-ghost" @click="reset()"><x-icon name="restart_alt" class="text-[18px]" />{{ __('Theme colours') }}</button>
                </div>
            </div>

            {{-- Preview: mini page with the real classes of the site --}}
            <div class="xl:col-span-3 xl:sticky xl:top-6">
                <p class="text-sm font-semibold text-stone-500 mb-2 flex items-center gap-2"><x-icon name="visibility" class="text-[18px]" />{{ __('Preview') }}</p>
                <div x-ref="preview" class="rounded-2xl overflow-hidden shadow-lg border border-stone-200 bg-surface text-on-surface font-body">
                    <div class="flex items-center justify-between gap-3 px-5 py-3 bg-surface shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-full bg-primary text-on-primary font-headline font-bold text-xs flex items-center justify-center">{{ app(\App\Cms\SiteSettings::class)->get('initials') }}</span>
                            <span class="font-headline font-bold text-sm">{{ app(\App\Cms\SiteSettings::class)->siteName() }}</span>
                        </div>
                        <span class="hidden sm:flex gap-4 text-xs"><span class="text-primary font-bold">{{ __('Home') }}</span><span class="text-on-surface-variant">{{ __('About') }}</span><span class="text-on-surface-variant">{{ __('Services') }}</span></span>
                        <span class="rounded-lg bg-btn text-on-btn text-xs font-semibold px-3 py-1.5">{{ __('Contact us') }}</span>
                    </div>
                    <div class="px-5 sm:px-8 py-8 space-y-4">
                        <span class="inline-flex items-center gap-2 rounded-full bg-secondary-container text-on-secondary-fixed text-xs font-semibold px-3 py-1"><span class="w-1.5 h-1.5 rounded-full bg-primary"></span>{{ __('Welcome') }}</span>
                        <p class="font-headline text-2xl sm:text-3xl font-bold leading-tight">{{ __('A title that makes you want to read') }}<span class="block text-primary italic font-normal text-xl sm:text-2xl mt-1">{{ __('and a subtitle in the primary colour') }}</span></p>
                        <p class="text-sm text-on-surface-variant">{{ __('A paragraph of text in the secondary text colour, to check that it stays easy to read.') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-xl bg-btn text-on-btn text-sm font-semibold px-4 py-2.5">{{ __('Main button') }}</span>
                            <span class="rounded-xl bg-surface-container-high text-on-surface text-sm font-semibold px-4 py-2.5">{{ __('Secondary button') }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low px-5 sm:px-8 py-6">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-primary">{{ __('Our services') }}</p>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach ([['lightbulb', __('Advice'), 'primary'], ['handyman', __('Realisation'), 'tertiary'], ['support_agent', __('Follow-up'), 'secondary']] as [$icon, $label, $tone])
                                <div class="rounded-xl bg-surface-container-lowest p-4 shadow-sm">
                                    <span class="w-9 h-9 rounded-lg flex items-center justify-center {{ ['primary' => 'bg-primary-fixed/40 text-on-primary-fixed', 'tertiary' => 'bg-tertiary-fixed/40 text-on-tertiary-fixed', 'secondary' => 'bg-secondary-container text-on-secondary-fixed'][$tone] }}"><x-icon :name="$icon" class="text-[20px]" /></span>
                                    <p class="mt-2 font-headline font-bold text-sm">{{ $label }}</p>
                                    <p class="text-xs font-semibold uppercase tracking-wider {{ ['primary' => 'text-primary', 'tertiary' => 'text-tertiary', 'secondary' => 'text-secondary'][$tone] }}">{{ __('Learn more') }}</p>
                                    <p class="mt-1 text-xs text-on-surface-variant">{{ __('A short description of the card.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="px-5 sm:px-8 py-6">
                        <div class="rounded-2xl bg-primary-fixed/40 p-5">
                            <p class="font-headline font-bold text-lg">{{ __('Ready to get started?') }}</p>
                            <p class="text-xs text-on-surface-variant mt-1">{{ __('A call to action to invite your visitors.') }}</p>
                            <span class="mt-3 inline-block rounded-xl bg-btn text-on-btn text-xs font-semibold px-4 py-2">{{ __('Contact us') }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container px-5 sm:px-8 py-4 text-[11px] text-on-surface-variant">© {{ now()->year }} · {{ __('Legal notice') }} · {{ __('Privacy policy') }}</div>
                </div>
            </div>
        </div>
    </form>

    @if ($options)
        <form method="POST" action="{{ route('admin.appearance.options') }}" class="mt-8 max-w-3xl"
              x-data x-init="$store.media.register(@js($media->mapWithKeys(fn ($m) => [$m->id => ['url' => $m->url(), 'name' => $m->original_name]])))">
            @csrf @method('PUT')
            <section class="ck-card p-5 sm:p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-bold flex items-center gap-2"><x-icon name="tune" class="text-[22px] text-emerald-700" />{{ __('Theme options') }}</h2>
                    <p class="ck-help mt-1">{{ __('Settings offered by the theme ":name".', ['name' => $theme->name()]) }}</p>
                </div>
                @foreach ($options as $option)
                    @php
                        $name = $option['name'];
                        $value = old('options.'.$name, $optionValues[$name] ?? ($option['default'] ?? null));
                    @endphp
                    @switch($option['type'])
                        @case('toggle')
                            <label class="flex items-start justify-between gap-4 rounded-xl bg-stone-50 p-4 cursor-pointer">
                                <span>
                                    <span class="block font-semibold text-sm">{{ __($option['label'] ?? $name) }}</span>
                                    @isset($option['help'])<span class="block text-sm text-stone-500">{{ __($option['help']) }}</span>@endisset
                                </span>
                                <input type="checkbox" name="options[{{ $name }}]" value="1" @checked($value) class="ck-toggle mt-0.5">
                            </label>
                            @break
                        @case('select')
                            <div>
                                <label for="o-{{ $name }}" class="ck-label">{{ __($option['label'] ?? $name) }}</label>
                                <select id="o-{{ $name }}" name="options[{{ $name }}]" class="ck-input">
                                    @foreach ((array) ($option['options'] ?? []) as $key => $label)
                                        <option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                                @isset($option['help'])<p class="ck-help">{{ __($option['help']) }}</p>@endisset
                            </div>
                            @break
                        @case('color')
                            <div>
                                <label for="o-{{ $name }}" class="ck-label">{{ __($option['label'] ?? $name) }}</label>
                                <input id="o-{{ $name }}" type="color" name="options[{{ $name }}]" value="{{ $value ?: '#000000' }}" class="h-11 w-20 rounded-lg border border-stone-300">
                            </div>
                            @break
                        @case('media')
                            <div x-data="{ v: {{ $value ? (int) $value : 'null' }} }">
                                <span class="ck-label">{{ __($option['label'] ?? $name) }}</span>
                                <input type="hidden" name="options[{{ $name }}]" :value="v || ''">
                                <div x-data="imageField(() => v, x => v = x)">@include('admin.fields.image-preview')</div>
                            </div>
                            @break
                        @case('textarea')
                            <div>
                                <label for="o-{{ $name }}" class="ck-label">{{ __($option['label'] ?? $name) }}</label>
                                <textarea id="o-{{ $name }}" name="options[{{ $name }}]" rows="3" class="ck-input">{{ $value }}</textarea>
                            </div>
                            @break
                        @default
                            <div>
                                <label for="o-{{ $name }}" class="ck-label">{{ __($option['label'] ?? $name) }}</label>
                                <input id="o-{{ $name }}" name="options[{{ $name }}]" value="{{ $value }}" class="ck-input">
                            </div>
                    @endswitch
                @endforeach
                <div class="flex justify-end">
                    <button type="submit" class="ck-btn-primary"><x-icon name="save" class="text-[18px]" />{{ __('Save the options') }}</button>
                </div>
            </section>
        </form>
    @endif
@endsection
