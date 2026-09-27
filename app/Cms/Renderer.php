<?php

namespace App\Cms;

use App\Cms\Languages\LanguageManager;
use App\Models\Block;
use App\Models\Media;
use Illuminate\Support\Collection;

/**
 * Helpers available in the block templates as $r.
 */
class Renderer
{
    /** @var Collection<int, Media> */
    private Collection $media;

    public function __construct(private Placeholders $ph, private SiteSettings $settings, private LanguageManager $languages)
    {
        $this->media = collect();
    }

    /** Preloads every image used by a list of blocks (a single query). */
    public function preload(iterable $blocks): static
    {
        $ids = [];
        foreach ($blocks as $block) {
            $this->collectImageIds($block instanceof Block ? $block->data : $block, $ids);
        }
        foreach (['logo_id', 'favicon_id', 'og_image_id'] as $key) {
            if ($id = $this->settings->get($key)) {
                $ids[] = $id;
            }
        }
        $this->media = Media::whereIn('id', array_unique($ids))->get()->keyBy('id');

        return $this;
    }

    private function collectImageIds(mixed $data, array &$ids): void
    {
        if (! is_array($data)) {
            return;
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->collectImageIds($value, $ids);
            } elseif (in_array($key, ['image', 'poster'], true) && is_int($value)) {
                $ids[] = $value;
            }
        }
    }

    /** Plain text → safe HTML (tags replaced, line breaks). */
    public function t(?string $value): string
    {
        return $this->ph->text($value);
    }

    /** Text between the quotation marks of the current language (“…”, « … »). */
    public function quote(?string $value): string
    {
        return str_replace(':text', $this->t($value), e(__('“:text”')));
    }

    /**
     * Translated sentence containing HTML: the sentence is escaped, then the
     * given (already safe) HTML fragments are inserted. Language packs can
     * therefore never inject HTML.
     *
     * @param  array<string, string>  $html  placeholder => safe HTML
     */
    public function sentence(string $text, array $html): string
    {
        $out = e(__($text));
        foreach ($html as $key => $fragment) {
            $out = str_replace(':'.$key, $fragment, $out);
        }

        return $out;
    }

    /** HTML from the editor (already sanitized when saved). */
    public function h(?string $value): string
    {
        return $this->ph->html($value);
    }

    /** Raw value for an attribute (Blade escapes it afterwards). */
    public function raw(?string $value): string
    {
        return $this->ph->raw($value);
    }

    public function img(mixed $id): ?Media
    {
        if (! $id) {
            return null;
        }

        return $this->media[$id] ??= Media::find($id);
    }

    public function setting(string $key): ?Media
    {
        return $this->img($this->settings->get($key));
    }

    /** @return string[] */
    public function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", (string) $text))));
    }

    /** @return string[] */
    public function tags(?string $text): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $text))));
    }

    public function isExternal(?string $url): bool
    {
        return (bool) preg_match('#^https?://#i', (string) $url) && ! str_starts_with((string) $url, site_url('/'));
    }

    /**
     * Resolves the link tags {phone}, {email} and {button}, and prefixes
     * internal links with the language of the page (/about → /fr/about).
     */
    public function href(?string $url): string
    {
        $url = match ($url) {
            '{phone}' => $this->settings->phoneHref(),
            '{email}' => 'mailto:'.$this->settings->get('email'),
            '{button}' => $this->href((string) $this->settings->get('cta_url')),
            default => $this->raw($url),
        };

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return site_url($this->localizePath($url));
        }

        return $url;
    }

    /** Adds the language prefix of the content being rendered to an internal path. */
    public function localizePath(string $path): string
    {
        $locale = $this->settings->contentLocale();
        if (! $locale || $locale === $this->languages->defaultLocale()) {
            return $path;
        }
        $prefix = $this->languages->pathPrefix($locale);

        return $path === $prefix || str_starts_with($path, $prefix.'/') || str_starts_with($path, '/media/') || str_starts_with($path, '/themes/')
            ? $path
            : $prefix.($path === '/' ? '' : $path);
    }

    /** Attributes of a link: href + new tab when external. */
    public function linkAttrs(?string $url): string
    {
        $href = $this->href($url);
        $attr = 'href="'.e($href ?: '#').'"';

        return $this->isExternal($href) ? $attr.' target="_blank" rel="noopener noreferrer"' : $attr;
    }

    public function background(?string $value): string
    {
        return match ($value) {
            'soft' => 'bg-surface-container-low',
            'strong' => 'bg-surface-container',
            default => 'bg-surface',
        };
    }

    /** Classes for each colour (full strings so that Tailwind detects them). */
    public function tone(?string $tone, string $kind): string
    {
        $tone = in_array($tone, ['primary', 'secondary', 'tertiary', 'error'], true) ? $tone : 'primary';

        return [
            'text' => ['primary' => 'text-primary', 'secondary' => 'text-secondary', 'tertiary' => 'text-tertiary', 'error' => 'text-error'],
            'soft' => ['primary' => 'bg-primary-fixed/40 text-on-primary-fixed', 'secondary' => 'bg-secondary-container text-on-secondary-fixed', 'tertiary' => 'bg-tertiary-fixed/40 text-on-tertiary-fixed', 'error' => 'bg-error-container text-on-error-container'],
            'solid' => ['primary' => 'bg-primary text-on-primary', 'secondary' => 'bg-secondary text-on-secondary', 'tertiary' => 'bg-tertiary text-on-tertiary', 'error' => 'bg-error text-on-error'],
            'hover' => ['primary' => 'group-hover:bg-primary group-hover:text-on-primary', 'secondary' => 'group-hover:bg-secondary group-hover:text-on-secondary', 'tertiary' => 'group-hover:bg-tertiary group-hover:text-on-tertiary', 'error' => 'group-hover:bg-error group-hover:text-on-error'],
            'pill' => ['primary' => 'bg-primary/10 text-primary', 'secondary' => 'bg-surface-container-highest text-secondary', 'tertiary' => 'bg-tertiary/10 text-tertiary', 'error' => 'bg-error/10 text-error'],
        ][$kind][$tone];
    }

    public function columns(?string $count): string
    {
        return match ((string) $count) {
            '2' => 'md:grid-cols-2',
            '4' => 'sm:grid-cols-2 lg:grid-cols-4',
            default => 'md:grid-cols-2 lg:grid-cols-3',
        };
    }

    /**
     * Privacy-friendly embed address for a YouTube or Vimeo link.
     */
    public function videoEmbed(?string $url): ?string
    {
        $url = trim((string) $url);
        if (preg_match('#^https://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1].'?rel=0';
        }
        if (preg_match('#^https://(?:www\.|player\.)?vimeo\.com/(?:video/)?(\d{5,12})#', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1].'?dnt=1';
        }

        return null;
    }
}
