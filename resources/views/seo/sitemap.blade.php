{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($pages as $page)
    <url>
        <loc>{{ $page->url() }}</loc>
        <lastmod>{{ $page->updated_at->toAtomString() }}</lastmod>
@if ($languages->isMultilingual() && $groups[$page->translationGroup()]->count() > 1)
@foreach ($groups[$page->translationGroup()] as $alt)
        <xhtml:link rel="alternate" hreflang="{{ $languages->find($alt->locale)?->htmlLang() ?? $alt->locale }}" href="{{ $alt->url() }}"/>
@endforeach
@endif
    </url>
@endforeach
</urlset>
