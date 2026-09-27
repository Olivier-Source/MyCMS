<?php

namespace App\Cms;

/**
 * Replaces the tags {phone}, {address}… with the site information.
 * Values are escaped: tags are safe in plain text as well as in sanitized HTML.
 */
class Placeholders
{
    private ?array $map = null;

    public function __construct(private SiteSettings $settings) {}

    /** @return array<string, string> tag => description (administration help) */
    public static function catalog(): array
    {
        $tags = [];
        foreach (SiteSettings::fields() as $field) {
            if (isset($field['tag'])) {
                $tags[$field['tag']] = $field['label'];
            }
        }

        return $tags + [
            'address' => __('Full address (street, postal code, city)'),
            'year' => __('Current year'),
        ];
    }

    public function map(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $map = [];
        foreach (SiteSettings::fields() as $key => $field) {
            if (isset($field['tag'])) {
                $map['{'.$field['tag'].'}'] = (string) $this->settings->get($key);
            }
        }
        $map['{address}'] = $this->settings->fullAddress();
        $map['{year}'] = (string) now()->year;

        return $this->map = $map;
    }

    public function flush(): void
    {
        $this->map = null;
    }

    /** Plain text → escaped HTML with tags replaced and line breaks kept. */
    public function text(?string $text, bool $nl2br = true): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        $html = strtr(e($text), array_map('e', $this->map()));

        return $nl2br ? nl2br($html, false) : $html;
    }

    /** Already sanitized HTML (rich text editor) → tags replaced by escaped values. */
    public function html(?string $html): string
    {
        return $html ? strtr($html, array_map('e', $this->map())) : '';
    }

    /** For attributes (links, alt…): raw value, escaped later by Blade. */
    public function raw(?string $text): string
    {
        return $text ? strtr($text, $this->map()) : '';
    }
}
