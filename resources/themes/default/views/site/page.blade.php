@extends('theme::layouts.site')

@section('content')
    @forelse ($blocks as $block)
        @php $d = \App\Cms\BlockRegistry::normalize($block->type, $block->data); @endphp
        <div id="{{ \Illuminate\Support\Str::slug($d['anchor'] ?? '') ?: 'section-'.$block->id }}" class="scroll-mt-24">
            @includeIf('theme::blocks.'.$block->type, ['d' => $d, 'block' => $block])
        </div>
    @empty
        <section class="max-w-3xl mx-auto px-4 sm:px-6 py-24 text-center">
            <h1 class="font-headline text-3xl font-bold">{{ $page->title }}</h1>
            <p class="mt-3 text-on-surface-variant">{{ __('This page has no content yet.') }}</p>
        </section>
    @endforelse
@endsection
