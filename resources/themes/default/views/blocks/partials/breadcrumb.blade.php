@if ($page && ! $page->is_home)
    <nav aria-label="{{ __('Breadcrumb') }}" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-secondary {{ $class ?? 'mb-6' }}">
        <a href="{{ $homeUrl }}" class="hover:text-primary transition-colors flex items-center gap-1"><x-icon name="home" class="text-[15px]" /><span>{{ __('Home') }}</span></a>
        <span class="text-outline-variant">/</span>
        <span class="text-primary font-bold">{{ $page->title }}</span>
    </nav>
@endif
