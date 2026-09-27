{{--
    Form field generated from the schema of a block (BlockRegistry).
    $field: definition; $model: JS expression of the object holding the value;
    $path: JS expression of the error prefix; $depth: nesting level.
--}}
@php
    $name = $field['name'];
    $type = $field['type'];
    $bind = $model.'.'.$name;
    $errExpr = "err({$path} + '{$name}')";
    $id = 'f-'.$depth.'-'.$name;
@endphp

@if ($type === 'heading')
    <div class="pt-4 mt-2 border-t border-stone-200">
        <p class="text-xs font-bold uppercase tracking-wider text-stone-500">{{ $field['label'] }}</p>
    </div>

@elseif ($type === 'toggle')
    <label class="flex items-start justify-between gap-4 rounded-xl bg-stone-50 px-4 py-3 cursor-pointer">
        <span class="text-sm font-semibold text-stone-800">{{ $field['label'] }}</span>
        <input type="checkbox" x-model="{{ $bind }}" class="ck-toggle mt-0.5">
    </label>

@elseif ($type === 'repeater')
    @php
        $item = 'item'.$depth;
        $index = 'i'.$depth;
        $defaults = \App\Cms\BlockRegistry::fieldDefaults($field['fields']);
        $labelKey = $field['item_label'] ?? null;
    @endphp
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <p class="ck-label mb-0">{{ $field['label'] }} <span class="font-normal text-stone-400" x-text="'(' + {{ $bind }}.length + ')'"></span></p>
            <div class="flex gap-1" x-show="{{ $bind }}.length > 1">
                <button type="button" class="text-xs font-semibold text-stone-500 hover:text-stone-900 px-2 py-1" @click="toggleAll({{ $bind }}, true)">{{ __('Open all') }}</button>
                <button type="button" class="text-xs font-semibold text-stone-500 hover:text-stone-900 px-2 py-1" @click="toggleAll({{ $bind }}, false)">{{ __('Close all') }}</button>
            </div>
        </div>

        <template x-for="({{ $item }}, {{ $index }}) in {{ $bind }}" :key="{{ $item }}._key">
            <div class="rounded-2xl border border-stone-200 bg-stone-50/60">
                <div class="flex items-center gap-1 pl-3 pr-1.5 py-1.5">
                    <button type="button" class="flex-1 min-w-0 flex items-center gap-2 py-1.5 text-left" @click="{{ $item }}._open = !{{ $item }}._open" :aria-expanded="{{ $item }}._open">
                        <span class="inline-flex transition-transform" :class="{{ $item }}._open ? '' : '-rotate-90'"><x-icon name="expand_more" class="text-[20px] text-stone-400" /></span>
                        <span class="font-semibold text-sm truncate"
                              x-text="{{ $labelKey ? "({$item}['{$labelKey}'] || '').toString().replace(/<[^>]*>/g, '').slice(0, 70) || " : '' }}@js(__('Item')) + ' ' + ({{ $index }} + 1)"></span>
                    </button>
                    <button type="button" class="ck-icon-btn h-8 w-8" @click="move({{ $bind }}, {{ $index }}, -1)" :disabled="{{ $index }} === 0" title="{{ __('Move up') }}" aria-label="{{ __('Move up') }}"><x-icon name="arrow_upward" class="text-[18px]" /></button>
                    <button type="button" class="ck-icon-btn h-8 w-8" @click="move({{ $bind }}, {{ $index }}, 1)" :disabled="{{ $index }} === {{ $bind }}.length - 1" title="{{ __('Move down') }}" aria-label="{{ __('Move down') }}"><x-icon name="arrow_downward" class="text-[18px]" /></button>
                    <button type="button" class="ck-icon-btn h-8 w-8" @click="duplicate({{ $bind }}, {{ $index }})" title="{{ __('Duplicate') }}" aria-label="{{ __('Duplicate') }}"><x-icon name="content_copy" class="text-[18px]" /></button>
                    <button type="button" class="ck-icon-btn h-8 w-8 hover:!text-red-700 hover:!bg-red-50" @click="remove({{ $bind }}, {{ $index }})" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}"><x-icon name="delete" class="text-[18px]" /></button>
                </div>
                <div x-show="{{ $item }}._open" class="px-4 pb-4 pt-1 space-y-4 border-t border-stone-200 bg-white rounded-b-2xl">
                    @foreach ($field['fields'] as $sub)
                        @include('admin.fields.field', [
                            'field' => $sub,
                            'model' => $item,
                            'path' => "{$path} + '{$name}.' + {$index} + '.'",
                            'depth' => $depth + 1,
                        ])
                    @endforeach
                </div>
            </div>
        </template>

        <button type="button" class="ck-btn-secondary w-full border-dashed" @click="add({{ $bind }}, @js($defaults))"
                x-show="{{ $bind }}.length < {{ $field['max_items'] ?? 50 }}">
            <x-icon name="add" class="text-[18px]" />{{ $field['add_label'] ?? __('Add') }}
        </button>
    </div>

@else
    <div>
        <label class="ck-label" :for="'{{ $id }}-' + ({{ $depth ? 'i'.($depth - 1) : "''" }})">
            {{ $field['label'] }}@if ($field['required'] ?? false) <span class="text-red-600">*</span>@endif
        </label>

        @switch($type)
            @case('textarea')
                <textarea :id="'{{ $id }}-' + ({{ $depth ? 'i'.($depth - 1) : "''" }})" x-model="{{ $bind }}" rows="3" class="ck-input leading-relaxed"
                          x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 2 + 'px' })"
                          @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 2 + 'px'"></textarea>
                @break

            @case('rich')
                <div x-data="richText(() => {{ $bind }}, v => {{ $bind }} = v)" class="rounded-xl"></div>
                @break

            @case('select')
                <select :id="'{{ $id }}-' + ({{ $depth ? 'i'.($depth - 1) : "''" }})" x-model="{{ $bind }}" class="ck-input">
                    @foreach ($field['options'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @break

            @case('image')
                <div x-data="imageField(() => {{ $bind }}, v => {{ $bind }} = v)">
                    @include('admin.fields.image-preview')
                </div>
                @break

            @case('icon')
                <div x-data="iconField(() => {{ $bind }}, v => {{ $bind }} = v)" class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl border border-stone-200 bg-white flex items-center justify-center text-emerald-700">
                        <svg x-show="path" viewBox="0 -960 960 960" class="w-6 h-6" fill="currentColor" aria-hidden="true"><path :d="path"></path></svg>
                        <span x-show="!path" class="text-xs text-stone-400">—</span>
                    </span>
                    <button type="button" class="ck-btn-secondary py-2" @click="choose()"><span x-text="value ? @js(__('Change icon')) : @js(__('Choose an icon'))"></span></button>
                    <button type="button" class="ck-btn-ghost py-2" x-show="value" @click="clear()">{{ __('Remove') }}</button>
                </div>
                @break

            @case('link')
                <input type="text" :id="'{{ $id }}-' + ({{ $depth ? 'i'.($depth - 1) : "''" }})" x-model="{{ $bind }}" list="internal-links" class="ck-input" placeholder="{{ __('/my-page, https://… or {phone}') }}" autocomplete="off">
                <p class="ck-help">{{ __('One of your pages (start typing "/"), a web address, or {phone} / {email} / {button}.') }}</p>
                @break

            @default
                <input type="text" :id="'{{ $id }}-' + ({{ $depth ? 'i'.($depth - 1) : "''" }})" x-model="{{ $bind }}" class="ck-input" @if (isset($field['max'])) maxlength="{{ $field['max'] }}" @endif>
        @endswitch

        @if (! empty($field['help']))
            <p class="ck-help">{{ $field['help'] }}</p>
        @endif
        <p class="ck-error" x-show="{{ $errExpr }}" x-text="{{ $errExpr }}"></p>
    </div>
@endif
