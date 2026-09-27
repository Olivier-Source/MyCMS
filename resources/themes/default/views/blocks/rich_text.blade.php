@php $narrow = ($d['width'] ?? 'narrow') === 'narrow'; @endphp
<section class="w-full {{ $r->background($d['background'] ?? 'none') }}">
    <div class="{{ $narrow ? 'max-w-3xl' : 'max-w-7xl' }} mx-auto px-4 sm:px-6 {{ $narrow ? '' : 'lg:px-12' }} py-10 sm:py-14 space-y-6">
        @if (! empty($d['eyebrow']))<span class="block text-xs font-bold uppercase tracking-widest text-primary">{!! $r->t($d['eyebrow']) !!}</span>@endif
        @if (! empty($d['title']))<h2 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface">{!! $r->t($d['title']) !!}</h2>@endif
        <div class="prose-cms text-[0.95rem]">{!! $r->h($d['content'] ?? '') !!}</div>
        @include('theme::blocks.partials.buttons', ['buttons' => $d['buttons'] ?? [], 'class' => 'pt-2 flex flex-col sm:flex-row gap-3'])
    </div>
</section>
