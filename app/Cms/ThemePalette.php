<?php

namespace App\Cms;

use App\Cms\Themes\Theme;

/**
 * Site colours: 9 colours chosen in "Appearance", every shade (light
 * backgrounds, hovers, readable text on buttons…) is computed here.
 * Same algorithm as resources/js/admin/theme.js (live preview).
 *
 * Themes may offer their own ready-made palettes (theme.json → "palettes").
 */
class ThemePalette
{
    public const DEFAULT = 'ocean';

    public const KEYS = ['primary', 'secondary', 'accent', 'button', 'background', 'section', 'card', 'text', 'textMuted'];

    public const PRESETS = [
        'ocean' => ['name' => 'Ocean', 'colors' => ['primary' => '#2f6f8f', 'secondary' => '#5a6b75', 'accent' => '#c07f45', 'button' => '#2f6f8f', 'background' => '#f5f8fa', 'section' => '#eaf1f5', 'card' => '#ffffff', 'text' => '#1f2d36', 'textMuted' => '#4a5a64']],
        'sage' => ['name' => 'Sage', 'colors' => ['primary' => '#4a7c59', 'secondary' => '#6b6358', 'accent' => '#705c30', 'button' => '#4a7c59', 'background' => '#faf6f0', 'section' => '#f5f1ea', 'card' => '#ffffff', 'text' => '#2e3230', 'textMuted' => '#4a4e4a']],
        'graphite' => ['name' => 'Graphite', 'colors' => ['primary' => '#374151', 'secondary' => '#6b7280', 'accent' => '#b45309', 'button' => '#111827', 'background' => '#f9fafb', 'section' => '#f3f4f6', 'card' => '#ffffff', 'text' => '#111827', 'textMuted' => '#4b5563']],
        'sunset' => ['name' => 'Sunset', 'colors' => ['primary' => '#c2410c', 'secondary' => '#78716c', 'accent' => '#0f766e', 'button' => '#c2410c', 'background' => '#fffbf5', 'section' => '#fdf1e4', 'card' => '#ffffff', 'text' => '#292524', 'textMuted' => '#57534e']],
        'lavender' => ['name' => 'Lavender', 'colors' => ['primary' => '#6b5b95', 'secondary' => '#7a7085', 'accent' => '#b07a9e', 'button' => '#6b5b95', 'background' => '#f8f6fb', 'section' => '#f1edf7', 'card' => '#ffffff', 'text' => '#2d2a35', 'textMuted' => '#55505f']],
        'terracotta' => ['name' => 'Terracotta', 'colors' => ['primary' => '#b5654a', 'secondary' => '#7a6a5c', 'accent' => '#b8893f', 'button' => '#b5654a', 'background' => '#fbf7f2', 'section' => '#f5ede4', 'card' => '#fffdfa', 'text' => '#3a2e28', 'textMuted' => '#5e5048']],
        'rose' => ['name' => 'Rose', 'colors' => ['primary' => '#a8606f', 'secondary' => '#8a7a7d', 'accent' => '#b88a5c', 'button' => '#a8606f', 'background' => '#fcf7f7', 'section' => '#f7eeee', 'card' => '#ffffff', 'text' => '#3b2f31', 'textMuted' => '#635558']],
        'night' => ['name' => 'Night', 'colors' => ['primary' => '#8ecf9e', 'secondary' => '#b8ad9c', 'accent' => '#dcc48e', 'button' => '#8ecf9e', 'background' => '#1c1f1d', 'section' => '#242826', 'card' => '#2b302d', 'text' => '#ece8e1', 'textMuted' => '#b9bdb6']],
    ];

    /** @return array<string, array{0: string, 1: string}> key => [label, hint] */
    public static function fields(): array
    {
        return [
            'primary' => [__('Primary colour'), __('Highlighted titles, icons, links')],
            'secondary' => [__('Secondary colour'), __('Badges and secondary elements')],
            'accent' => [__('Accent colour'), __('Small details, quotes')],
            'button' => [__('Buttons'), __('Main buttons')],
            'background' => [__('Page background'), __('Main background colour')],
            'section' => [__('Section background'), __('Alternating bands, boxes')],
            'card' => [__('Card background'), __('Cards and highlighted blocks')],
            'text' => [__('Main text'), __('Titles and paragraphs')],
            'textMuted' => [__('Secondary text'), __('Descriptions, discreet texts')],
        ];
    }

    /** @return array<string, array{name: string, colors: array<string, string>}> Core palettes + those of the theme */
    public static function presets(?Theme $theme = null): array
    {
        $presets = [];
        foreach ($theme?->palettes() ?? [] as $key => $palette) {
            $presets[$key] = ['name' => $palette['name'], 'colors' => self::sanitize($palette['colors'])];
        }

        return $presets + self::PRESETS;
    }

    /** Default colours: the default palette of the theme, or "Ocean". */
    public static function defaults(?Theme $theme = null): array
    {
        $palette = $theme?->palettes()[$theme->defaultPalette()] ?? null;

        return $palette ? self::sanitize($palette['colors']) : self::PRESETS[self::DEFAULT]['colors'];
    }

    public static function isHex(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
    }

    /** Valid values only, completed with the default palette. */
    public static function sanitize(array $colors): array
    {
        $base = self::PRESETS[self::DEFAULT]['colors'];
        $out = [];
        foreach (self::KEYS as $key) {
            $out[$key] = self::isHex($colors[$key] ?? null) ? strtolower($colors[$key]) : $base[$key];
        }

        return $out;
    }

    /** @return array<string, string> token => "r g b" for the --c-* CSS variables */
    public static function cssVariables(array $colors): array
    {
        return array_map(fn ($hex) => implode(' ', self::rgb($hex)), self::derive(self::sanitize($colors)));
    }

    public static function derive(array $p): array
    {
        [$P, $S, $A, $B] = [$p['primary'], $p['secondary'], $p['accent'], $p['button']];
        [$bg, $sec, $txt, $muted] = [$p['background'], $p['section'], $p['text'], $p['textMuted']];
        $dark = self::luminance($bg) < 0.2;

        return [
            'primary' => $P, 'on-primary' => self::onColor($P),
            'primary-container' => self::mix($P, $bg, .3), 'on-primary-container' => self::mix($P, $txt, .75),
            'primary-fixed' => self::mix($P, $bg, .72), 'primary-fixed-dim' => self::mix($P, $bg, .45),
            'on-primary-fixed' => self::mix($P, $txt, .65), 'on-primary-fixed-variant' => self::mix($P, $txt, .35),

            'secondary' => $S, 'on-secondary' => self::onColor($S),
            'secondary-container' => self::mix($S, $bg, .84), 'secondary-fixed' => self::mix($S, $bg, .84),
            'on-secondary-container' => self::mix($S, $txt, .3), 'on-secondary-fixed' => self::mix($S, $txt, .7),
            'on-secondary-fixed-variant' => self::mix($S, $txt, .4),

            'tertiary' => $A, 'on-tertiary' => self::onColor($A),
            'tertiary-container' => self::mix($A, $bg, .3), 'tertiary-fixed' => self::mix($A, $bg, .72),
            'on-tertiary-fixed' => self::mix($A, $txt, .7), 'on-tertiary-fixed-variant' => self::mix($A, $txt, .4),

            'btn' => $B, 'on-btn' => self::onColor($B),
            'btn-hover' => self::luminance($B) < 0.06 ? self::mix($B, '#ffffff', .15) : self::mix($B, '#000000', .15),

            'surface' => $bg,
            'surface-container-lowest' => $p['card'],
            'surface-container-low' => $sec,
            'surface-container' => self::mix($sec, $txt, .03),
            'surface-container-high' => self::mix($sec, $txt, .06),
            'surface-container-highest' => self::mix($sec, $txt, .1),
            'surface-variant' => self::mix($sec, $txt, .1),
            'on-surface' => $txt,
            'on-surface-variant' => $muted,
            'outline-variant' => self::mix($muted, $bg, .7),

            'error' => $dark ? '#ff8a80' : '#b83230', 'on-error' => $dark ? '#3a0a08' : '#ffffff',
            'error-container' => $dark ? '#5c1a17' : '#ffdad8', 'on-error-container' => $dark ? '#ffdad8' : '#690005',
        ];
    }

    /* ---------- Colour tools ---------- */

    public static function rgb(string $hex): array
    {
        $n = hexdec(ltrim($hex, '#'));

        return [($n >> 16) & 255, ($n >> 8) & 255, $n & 255];
    }

    public static function hex(array $rgb): string
    {
        return '#'.implode('', array_map(fn ($v) => str_pad(dechex((int) max(0, min(255, round($v)))), 2, '0', STR_PAD_LEFT), $rgb));
    }

    public static function mix(string $a, string $b, float $t): string
    {
        $x = self::rgb($a);
        $y = self::rgb($b);

        return self::hex(array_map(fn ($i) => $x[$i] + ($y[$i] - $x[$i]) * $t, [0, 1, 2]));
    }

    public static function luminance(string $hex): float
    {
        $c = array_map(function ($v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    public static function contrast(string $a, string $b): float
    {
        [$l1, $l2] = [self::luminance($a), self::luminance($b)];

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    public static function onColor(string $c): string
    {
        return self::contrast($c, '#ffffff') >= self::contrast($c, '#1d1d1b') ? '#ffffff' : '#1d1d1b';
    }
}
