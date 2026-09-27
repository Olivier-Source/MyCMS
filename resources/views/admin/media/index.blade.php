@extends('admin.layout', ['title' => __('Pictures')])

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Pictures') }}</h1>
            <p class="mt-1 text-stone-500">{{ __('All the images of your site. Add a short description to each one: it is read to visually impaired people and helps search engines.') }}</p>
        </div>
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" x-data @change="$el.submit()">
            @csrf
            <label class="ck-btn-primary cursor-pointer">
                <x-icon name="upload" class="text-[20px]" />{{ __('Add an image') }}
                <input type="file" name="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
        </form>
    </div>

    @unless ($gdAvailable)
        <div class="mb-6 rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
            {{ __('The PHP "GD" extension is missing on this server: images are kept as they are (no metadata removal, no resizing). Ask your host to enable it.') }}
        </div>
    @endunless

    @if ($items->isEmpty())
        <div class="ck-card p-10 text-center">
            <x-icon name="photo_library" class="text-[48px] text-stone-300" />
            <p class="mt-3 font-semibold">{{ __('No image yet.') }}</p>
            <p class="text-stone-500">{{ __('Accepted formats: JPG, PNG or WebP, up to :max MB.', ['max' => (int) round(config('mycms.media.max_kb') / 1024)]) }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($items as $media)
                <div class="ck-card overflow-hidden flex flex-col">
                    <a href="{{ $media->url() }}" target="_blank" rel="noopener" class="block bg-stone-100">
                        <img src="{{ $media->url() }}" alt="{{ $media->alt }}" class="w-full aspect-[4/3] object-cover" loading="lazy">
                    </a>
                    <div class="p-4 flex-1 flex flex-col gap-3">
                        <p class="text-xs text-stone-500 truncate">{{ $media->original_name }} · {{ $media->width }}×{{ $media->height }} · {{ $media->humanSize() }}</p>
                        <form method="POST" action="{{ route('admin.media.update', $media) }}" class="flex gap-2">
                            @csrf @method('PUT')
                            <input name="alt" value="{{ $media->alt }}" maxlength="200" class="ck-input py-2 text-sm" placeholder="{{ __('Description of the image') }}" aria-label="{{ __('Description of the image') }}">
                            <button type="submit" class="ck-icon-btn shrink-0" title="{{ __('Save the description') }}" aria-label="{{ __('Save the description') }}"><x-icon name="save" class="text-[20px]" /></button>
                        </form>
                        <form method="POST" action="{{ route('admin.media.destroy', $media) }}" class="mt-auto" @submit="if (!confirm(@js(__('Permanently delete this image?')))) $event.preventDefault()">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-red-700 hover:underline inline-flex items-center gap-1"><x-icon name="delete" class="text-[16px]" />{{ __('Delete') }}</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $items->links() }}</div>
    @endif
@endsection
