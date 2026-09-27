@extends('admin.layout', ['title' => __('Site information')])

@section('content')
    @php
        $group = $groups[$current];
        $fields = $lang ? array_filter($group['fields'], fn ($f) => $f['translatable'] ?? false) : $group['fields'];
    @endphp

    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Site information') }}</h1>
        <p class="mt-1 text-stone-500">{{ __('This information is used everywhere on the site: change it only once, here.') }}</p>
    </div>

    @if (count($siteLocales) > 1)
        <nav class="mb-6 flex flex-wrap items-center gap-2" aria-label="{{ __('Languages') }}">
            <span class="text-sm font-semibold text-stone-500 mr-1 flex items-center gap-1"><x-icon name="translate" class="text-[18px]" />{{ __('Language:') }}</span>
            @foreach ($siteLocales as $code)
                @php $isCurrent = $code === $defaultLocale ? ! $lang : $lang === $code; @endphp
                <a href="{{ route('admin.settings.edit', ['group' => $current, 'lang' => $code === $defaultLocale ? null : $code]) }}"
                   class="rounded-xl px-3.5 py-1.5 text-sm font-semibold border transition {{ $isCurrent ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-stone-700 border-stone-200 hover:border-stone-400' }}">
                    {{ $languages->find($code)->nativeName() }}{{ $code === $defaultLocale ? ' ('.__('default').')' : '' }}
                </a>
            @endforeach
        </nav>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
        <nav class="ck-card p-2 lg:sticky lg:top-6 flex lg:flex-col gap-1 overflow-x-auto" aria-label="{{ __('Categories') }}">
            @foreach ($groups as $key => $g)
                @php $hasTranslatable = collect($g['fields'])->contains(fn ($f) => $f['translatable'] ?? false); @endphp
                @continue($lang && ! $hasTranslatable)
                <a href="{{ route('admin.settings.edit', ['group' => $key, 'lang' => $lang]) }}" class="ck-nav-link whitespace-nowrap {{ $key === $current ? 'is-active' : '' }}">
                    <x-icon :name="$g['icon']" class="text-[20px]" /><span>{{ $g['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.settings.update', ['group' => $current, 'lang' => $lang]) }}" class="lg:col-span-3 space-y-6"
              x-data x-init="$store.media.register(@js($media->mapWithKeys(fn ($m) => [$m->id => ['url' => $m->url(), 'name' => $m->original_name]])))">
            @csrf @method('PUT')
            <section class="ck-card p-5 sm:p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-bold flex items-center gap-2"><x-icon :name="$group['icon']" class="text-[22px] text-emerald-700" />{{ $group['label'] }}</h2>
                    <p class="ck-help mt-1">{{ $group['intro'] }}</p>
                    @if ($lang)
                        <p class="mt-3 rounded-xl bg-sky-50 border border-sky-200 px-4 py-3 text-sm text-sky-900">
                            {{ __('Translation into :language: leave a field empty to use the value of the default language (shown in grey).', ['language' => $languages->find($lang)->nativeName()]) }}
                        </p>
                    @endif
                </div>

                @forelse ($fields as $key => $field)
                    @php $value = old($key, $lang ? ($translations[$key] ?? null) : ($values[$key] ?? null)); @endphp

                    @if ($field['type'] === 'toggle')
                        <label class="flex items-start justify-between gap-4 rounded-xl bg-stone-50 p-4 cursor-pointer">
                            <span>
                                <span class="block font-semibold text-sm">{{ $field['label'] }}</span>
                                @isset($field['help'])<span class="block text-sm text-stone-500">{{ $field['help'] }}</span>@endisset
                            </span>
                            <input type="checkbox" name="{{ $key }}" value="1" @checked($value) class="ck-toggle mt-0.5">
                        </label>

                    @elseif ($field['type'] === 'media')
                        <div x-data="{ v: {{ $value ? (int) $value : 'null' }} }">
                            <span class="ck-label">{{ $field['label'] }}</span>
                            <input type="hidden" name="{{ $key }}" :value="v || ''">
                            <div x-data="imageField(() => v, x => v = x)">@include('admin.fields.image-preview')</div>
                            @isset($field['help'])<p class="ck-help">{{ $field['help'] }}</p>@endisset
                        </div>

                    @elseif ($field['type'] === 'icon')
                        <div x-data="{ v: @js($value ?? '') }">
                            <span class="ck-label">{{ $field['label'] }}</span>
                            <input type="hidden" name="{{ $key }}" :value="v">
                            <div x-data="iconField(() => v, x => v = x)" class="flex items-center gap-3">
                                <span class="w-11 h-11 rounded-xl border border-stone-200 bg-white flex items-center justify-center text-emerald-700">
                                    <svg x-show="path" viewBox="0 -960 960 960" class="w-6 h-6" fill="currentColor" aria-hidden="true"><path :d="path"></path></svg>
                                    <span x-show="!path" class="text-xs text-stone-400">—</span>
                                </span>
                                <button type="button" class="ck-btn-secondary py-2" @click="choose()">{{ __('Choose an icon') }}</button>
                                <button type="button" class="ck-btn-ghost py-2" x-show="value" @click="clear()">{{ __('Remove') }}</button>
                            </div>
                        </div>

                    @elseif (in_array($field['type'], ['hours', 'links'], true))
                        @php [$a, $b] = $field['type'] === 'hours' ? ['label', 'value'] : ['label', 'url']; @endphp
                        <div x-data="{ rows: @js(array_values($value ?? [])) }">
                            <span class="ck-label">{{ $field['label'] }}</span>
                            <div class="space-y-2">
                                <template x-for="(row, i) in rows" :key="i">
                                    <div class="flex items-center gap-2">
                                        <input type="text" :name="'{{ $key }}[' + i + '][{{ $a }}]'" x-model="row.{{ $a }}" class="ck-input"
                                               placeholder="{{ $field['type'] === 'hours' ? __('E.g. Monday – Friday') : __('E.g. Instagram') }}" aria-label="{{ $field['type'] === 'hours' ? __('Days') : __('Name') }}">
                                        <input type="{{ $field['type'] === 'hours' ? 'text' : 'url' }}" :name="'{{ $key }}[' + i + '][{{ $b }}]'" x-model="row.{{ $b }}" class="ck-input"
                                               placeholder="{{ $field['type'] === 'hours' ? __('E.g. 9:00 – 18:00') : 'https://…' }}" aria-label="{{ $field['type'] === 'hours' ? __('Hours') : __('Address') }}">
                                        <button type="button" class="ck-icon-btn shrink-0 hover:!text-red-700" @click="rows.splice(i, 1)" aria-label="{{ __('Remove the line') }}"><x-icon name="delete" class="text-[20px]" /></button>
                                    </div>
                                </template>
                            </div>
                            <button type="button" class="ck-btn-secondary mt-2 py-2" @click="rows.push({ {{ $a }}: '', {{ $b }}: '' })"><x-icon name="add" class="text-[18px]" />{{ __('Add a line') }}</button>
                        </div>

                    @else
                        <div>
                            <label for="s-{{ $key }}" class="ck-label">{{ $field['label'] }}@if (($field['required'] ?? false) && ! $lang) <span class="text-red-600">*</span>@endif</label>
                            @php $placeholder = $lang ? (string) ($values[$key] ?? '') : ''; @endphp
                            @if ($field['type'] === 'textarea')
                                <textarea id="s-{{ $key }}" name="{{ $key }}" rows="3" class="ck-input" placeholder="{{ $placeholder }}">{{ $value }}</textarea>
                            @else
                                <input id="s-{{ $key }}" name="{{ $key }}" value="{{ $value }}" placeholder="{{ $placeholder }}"
                                       type="{{ ['email' => 'email', 'tel' => 'tel', 'url' => 'url', 'date' => 'date'][$field['type']] ?? 'text' }}"
                                       @if ($field['type'] === 'number') inputmode="decimal" @endif
                                       @if ($field['type'] === 'link') list="settings-links" @endif
                                       @isset($field['max']) maxlength="{{ $field['max'] }}" @endisset
                                       class="ck-input">
                            @endif
                            @isset($field['tag'])
                                <p class="ck-help">{{ __('In the texts:') }} <code class="rounded bg-stone-100 px-1.5 py-0.5 text-emerald-800">{{ '{'.$field['tag'].'}' }}</code>@isset($field['help']) — {{ $field['help'] }}@endisset</p>
                            @else
                                @isset($field['help'])<p class="ck-help">{{ $field['help'] }}</p>@endisset
                            @endisset
                            @error($key)<p class="ck-error">{{ $message }}</p>@enderror
                        </div>
                    @endif
                @empty
                    <p class="text-stone-500">{{ __('Nothing to translate in this category.') }}</p>
                @endforelse
            </section>

            <datalist id="settings-links">
                <option value="/contact"></option>
                <option value="{phone}">{{ __('Call the phone number') }}</option>
                <option value="{email}">{{ __('Write an e-mail') }}</option>
            </datalist>

            <div class="flex justify-end">
                <button type="submit" class="ck-btn-primary"><x-icon name="save" class="text-[18px]" />{{ __('Save') }}</button>
            </div>
        </form>
    </div>
@endsection
