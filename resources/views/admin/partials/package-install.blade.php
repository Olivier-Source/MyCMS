{{-- Install form shared by themes and languages. $action, $title, $help, $placeholder --}}
<section class="ck-card p-5 sm:p-6" x-data="{ source: '{{ old('source', 'url') }}', busy: false }">
    <h2 class="text-lg font-bold flex items-center gap-2"><x-icon name="download" class="text-[22px] text-emerald-700" />{{ $title }}</h2>
    <p class="ck-help mt-1">{{ $help }}</p>

    @error('install')
        <div class="mt-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-900" role="alert">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-4 space-y-4" @submit="busy = true">
        @csrf
        <div class="inline-flex rounded-xl bg-stone-100 p-1" role="radiogroup">
            <label class="cursor-pointer rounded-lg px-4 py-2 text-sm font-semibold transition" :class="source === 'url' ? 'bg-white shadow-sm text-stone-900' : 'text-stone-500'">
                <input type="radio" name="source" value="url" x-model="source" class="sr-only">{{ __('From a Git link') }}
            </label>
            <label class="cursor-pointer rounded-lg px-4 py-2 text-sm font-semibold transition" :class="source === 'zip' ? 'bg-white shadow-sm text-stone-900' : 'text-stone-500'">
                <input type="radio" name="source" value="zip" x-model="source" class="sr-only">{{ __('From a ZIP file') }}
            </label>
        </div>

        <div x-show="source === 'url'">
            <label for="pkg-url" class="ck-label">{{ __('Address of the public repository or of the .zip file') }}</label>
            <input id="pkg-url" name="url" type="url" value="{{ old('url') }}" class="ck-input font-mono text-sm" placeholder="{{ $placeholder }}" :disabled="source !== 'url'">
            <p class="ck-help">{{ __('GitHub, GitLab or any public Git repository. Add #name to choose a branch or a tag (e.g. …/my-repo#v1.2).') }}</p>
        </div>
        <div x-show="source === 'zip'" x-cloak>
            <label for="pkg-zip" class="ck-label">{{ __('ZIP archive') }}</label>
            <input id="pkg-zip" name="zip" type="file" accept=".zip,application/zip" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-semibold file:text-emerald-800" :disabled="source !== 'zip'">
        </div>

        <button type="submit" class="ck-btn-primary" :disabled="busy">
            <x-icon name="download" class="text-[18px]" /><span x-text="busy ? @js(__('Installing…')) : @js(__('Install'))"></span>
        </button>
    </form>
</section>
