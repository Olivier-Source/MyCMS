<?php

namespace App\Cms;

use App\Models\Media;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans and validates the data typed in the administration, from the block
 * schema (BlockRegistry). Nothing stored can contain a script, a "javascript:"
 * link or unauthorised HTML.
 */
class ContentSanitizer
{
    /** @var array<string, string> field path => error message */
    private array $errors = [];

    private ?HtmlSanitizer $html = null;

    /**
     * @return array{0: array, 1: array<string, string>} [clean data, errors]
     */
    public function clean(array $fields, mixed $input): array
    {
        $this->errors = [];
        $data = $this->cleanFields($fields, is_array($input) ? $input : [], '');

        return [$data, $this->errors];
    }

    private function cleanFields(array $fields, array $input, string $prefix): array
    {
        $out = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'heading') {
                continue;
            }
            $name = $field['name'];
            $path = $prefix.$name;
            $value = $this->cleanValue($field, $input[$name] ?? null, $path);

            if (($field['required'] ?? false) && ($value === '' || $value === null || $value === [])) {
                $this->errors[$path] = __('This field is required.');
            }
            $out[$name] = $value;
        }

        return $out;
    }

    private function cleanValue(array $field, mixed $value, string $path): mixed
    {
        switch ($field['type']) {
            case 'text':
                return $this->plain($value, $field['max'] ?? 500, false);

            case 'textarea':
                return $this->plain($value, $field['max'] ?? 5000, true);

            case 'rich':
                return $this->rich($value);

            case 'number':
                $v = str_replace(',', '.', trim((string) $value));

                return is_numeric($v) ? $v : '';

            case 'toggle':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'select':
                $value = (string) $value;

                return array_key_exists($value, $field['options']) ? $value : (string) ($field['default'] ?? array_key_first($field['options']));

            case 'icon':
                $value = trim((string) $value);

                return preg_match('/^[a-z0-9_]{1,40}$/', $value) ? $value : '';

            case 'image':
                $id = (int) $value;

                return $id > 0 && Media::whereKey($id)->exists() ? $id : null;

            case 'link':
                $link = $this->link($value);
                if ($link === false) {
                    $this->errors[$path] = __('Invalid address: use an internal link (/my-page), https://…, tel:… or mailto:…');

                    return '';
                }

                return $link;

            case 'repeater':
                $items = [];
                foreach (array_values(is_array($value) ? $value : []) as $i => $item) {
                    if ($i >= ($field['max_items'] ?? 50)) {
                        break;
                    }
                    $items[] = $this->cleanFields($field['fields'], is_array($item) ? $item : [], $path.'.'.$i.'.');
                }

                return $items;
        }

        return null;
    }

    public function plain(mixed $value, int $max = 500, bool $multiline = false): string
    {
        $value = is_scalar($value) ? (string) $value : '';
        $value = strip_tags($value);
        $value = str_replace("\r\n", "\n", $value);
        $value = $multiline ? preg_replace("/\n{3,}/", "\n\n", $value) : preg_replace('/\s+/u', ' ', $value);
        // Invisible control characters
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return mb_substr(trim($value), 0, $max);
    }

    /**
     * Safe link: internal path, anchor, http(s), tel:, mailto:. False when refused.
     */
    public function link(mixed $value): string|false
    {
        $value = trim(is_scalar($value) ? (string) $value : '');
        if ($value === '') {
            return '';
        }
        // Link tags: {phone}, {email}, {button}
        if (preg_match('/^\{(phone|email|button)\}$/', $value)) {
            return $value;
        }
        if (mb_strlen($value) > 500 || preg_match('/[\s<>"\'`]/u', $value)) {
            return false;
        }
        if (preg_match('#^(/(?!/)|\#|https?://|tel:\+?[0-9 .\-]+$|mailto:[^@\s]+@[^@\s]+$)#i', $value)) {
            return $value;
        }

        return false;
    }

    public function rich(mixed $value): string
    {
        $value = is_string($value) ? mb_substr($value, 0, 50000) : '';
        if (trim(strip_tags($value)) === '') {
            return '';
        }

        $clean = $this->htmlSanitizer()->sanitize($value);
        // The editor produces <h1>: they are brought down to a consistent level in the page
        $clean = preg_replace('#<(/?)h1>#', '<$1h3>', $clean);

        return trim($clean);
    }

    private function htmlSanitizer(): HtmlSanitizer
    {
        if ($this->html) {
            return $this->html;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(50000);

        foreach (['p', 'div', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote'] as $tag) {
            $config = $config->allowElement($tag);
        }
        $config = $config->allowElement('a', ['href']);

        return $this->html = new HtmlSanitizer($config);
    }
}
