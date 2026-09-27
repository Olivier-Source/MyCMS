@extends('admin.layout', ['title' => __('Add a section')])

@section('content')
    <a href="{{ route('admin.pages.edit', $page) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-stone-900 mb-4"><x-icon name="arrow_back" class="text-[18px]" />{{ $page->title }}</a>
    <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Which kind of section?') }}</h1>
    <p class="mt-1 mb-6 text-stone-500">{{ __('Choose a model: you will then fill in the texts and pictures.') }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($registry as $type => $def)
            <form method="POST" action="{{ route('admin.blocks.store', $page) }}">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="after" value="{{ $after }}">
                <button type="submit" class="ck-card w-full h-full text-left p-5 hover:border-emerald-400 hover:shadow-md focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-600/25 transition group">
                    <span class="flex items-start justify-between gap-2">
                        <span class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center group-hover:bg-emerald-700 group-hover:text-white transition"><x-icon :name="$def['icon']" class="text-[24px]" /></span>
                        @if (! empty($def['theme']))<span class="ck-badge bg-violet-50 text-violet-800">{{ __('Theme') }}</span>@endif
                    </span>
                    <span class="mt-3 block font-bold">{{ $def['label'] }}</span>
                    <span class="mt-1 block text-sm text-stone-500">{{ $def['description'] }}</span>
                </button>
            </form>
        @endforeach
    </div>
@endsection
