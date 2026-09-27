<?php

namespace App\Cms\Languages;

/**
 * A language pack: a folder with a language.json manifest and JSON files only
 * (no PHP code is ever executed from a language pack):
 *  - messages.json   interface and theme strings ("English text" => "translation")
 *  - validation.json, passwords.json, pagination.json, auth.json (optional)
 */
final class LanguagePack
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $path,
        public readonly array $manifest,
        public readonly bool $bundled,
    ) {}

    public function name(): string
    {
        return (string) ($this->manifest['name'] ?? $this->code);
    }

    /** Name in the language itself ("Français", "Deutsch"…) */
    public function nativeName(): string
    {
        return (string) ($this->manifest['native'] ?? $this->name());
    }

    public function version(): string
    {
        return (string) ($this->manifest['version'] ?? '1.0.0');
    }

    public function author(): string
    {
        return (string) ($this->manifest['author'] ?? '');
    }

    public function direction(): string
    {
        return ($this->manifest['direction'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr';
    }

    /** Value for <html lang> and hreflang ("fr", "pt-BR") */
    public function htmlLang(): string
    {
        return str_replace('_', '-', $this->code);
    }

    /** Value for og:locale ("fr_FR") */
    public function ogLocale(): string
    {
        return (string) ($this->manifest['og_locale'] ?? (str_contains($this->code, '_') ? $this->code : $this->code.'_'.strtoupper($this->code)));
    }

    /** URL prefix of the pages written in this language ("fr", "pt-br") */
    public function urlPrefix(): string
    {
        return strtolower(str_replace('_', '-', $this->code));
    }

    /** @return array<string, mixed> Content of one of the pack's JSON files */
    public function file(string $name): array
    {
        if (! $this->path || ! preg_match('/^[a-z_-]+$/', $name) || ! is_file($file = $this->path.'/'.$name.'.json')) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : [];
    }

    /** Number of translated interface strings */
    public function stringsCount(): int
    {
        return count($this->file('messages'));
    }
}
