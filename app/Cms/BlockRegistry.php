<?php

namespace App\Cms;

use App\Cms\Themes\ThemeManager;

/**
 * Section types ("blocks") available to build the pages.
 *
 * Each block describes its fields; the administration form, the validation
 * and the sanitizing are generated from this description. The public
 * template is the view "theme::blocks.{type}" of the active theme.
 *
 * Themes may add their own blocks (see "blocks" in theme.json).
 *
 * Field types: text, textarea, rich, image, icon, select, toggle, link, number,
 * repeater (with "fields"), heading (a simple separator in the form).
 */
class BlockRegistry
{
    /** @var array<string, array> Definitions per language */
    private static array $cache = [];

    public static function all(): array
    {
        $key = app()->getLocale().'|'.self::themeKey();

        return self::$cache[$key] ??= array_map(function (array $def) {
            // Common to every section: an anchor for "/page#anchor" links
            $def['fields'][] = self::heading(__('Advanced setting'));
            $def['fields'][] = self::f('anchor', __('Section anchor'), 'text', [
                'max' => 40,
                'help' => __('Optional. E.g. "prices" lets you link directly to this section: /my-page#prices'),
            ]);

            return $def;
        }, self::definitions() + self::themeBlocks());
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    public static function exists(string $type): bool
    {
        return isset(self::all()[$type]);
    }

    /** Default values of a new block. */
    public static function defaults(string $type): array
    {
        return self::fieldDefaults(self::get($type)['fields'] ?? []);
    }

    /**
     * Completes stored data with every expected key (including in repeated
     * items): templates never have a missing key.
     */
    public static function normalize(string $type, ?array $data): array
    {
        $def = self::get($type);

        return $def ? self::normalizeFields($def['fields'], $data ?? []) : ($data ?? []);
    }

    private static function normalizeFields(array $fields, array $data): array
    {
        $defaults = self::fieldDefaults($fields);
        $out = array_replace($defaults, array_intersect_key($data, $defaults));

        foreach ($fields as $f) {
            if ($f['type'] === 'repeater') {
                $out[$f['name']] = array_values(array_map(
                    fn ($item) => self::normalizeFields($f['fields'], is_array($item) ? $item : []),
                    is_array($out[$f['name']]) ? $out[$f['name']] : []
                ));
            }
        }

        return $out;
    }

    public static function fieldDefaults(array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            if ($f['type'] === 'heading') {
                continue;
            }
            $out[$f['name']] = $f['default'] ?? match ($f['type']) {
                'repeater' => [],
                'toggle' => false,
                'select' => array_key_first($f['options']),
                'image' => null,
                default => '',
            };
        }

        return $out;
    }

    /* ------------------------------------------------------------------ */

    private static function themeKey(): string
    {
        try {
            return app(ThemeManager::class)->active()->slug;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Blocks declared by the active theme (theme.json → "blocks").
     * Labels are translated with the theme's language files.
     */
    private static function themeBlocks(): array
    {
        try {
            $theme = app(ThemeManager::class)->active();
        } catch (\Throwable) {
            return [];
        }

        $blocks = [];
        foreach ($theme->blocks() as $type => $def) {
            $fields = self::themeFields($def['fields']);
            if ($fields) {
                $blocks[$type] = [
                    'label' => __((string) ($def['label'] ?? $type)),
                    'icon' => (string) ($def['icon'] ?? 'extension'),
                    'description' => __((string) ($def['description'] ?? '')),
                    'fields' => $fields,
                    'theme' => $theme->slug,
                ];
            }
        }

        return $blocks;
    }

    /** Validates and translates the fields of a theme block. */
    private static function themeFields(array $fields, int $depth = 0): array
    {
        $types = ['text', 'textarea', 'rich', 'image', 'icon', 'select', 'toggle', 'link', 'number', 'repeater', 'heading'];
        $out = [];
        foreach ($fields as $field) {
            if (! is_array($field) || ! in_array($field['type'] ?? 'text', $types, true)) {
                continue;
            }
            $type = $field['type'] ?? 'text';
            if ($type === 'heading') {
                $out[] = self::heading(__((string) ($field['label'] ?? '')));

                continue;
            }
            if (! preg_match('/^[a-z][a-z0-9_]{0,40}$/', (string) ($field['name'] ?? ''))) {
                continue;
            }
            $extra = array_intersect_key($field, array_flip(['default', 'required', 'max', 'max_items', 'item_label']));
            if (isset($field['help'])) {
                $extra['help'] = __((string) $field['help']);
            }
            if ($type === 'select') {
                $options = array_map(fn ($l) => __((string) $l), (array) ($field['options'] ?? []));
                if (! $options) {
                    continue;
                }
                $extra['options'] = $options;
            }
            if ($type === 'repeater') {
                if ($depth > 1 || ! ($extra['fields'] = self::themeFields((array) ($field['fields'] ?? []), $depth + 1))) {
                    continue;
                }
                $extra['add_label'] = __((string) ($field['add_label'] ?? 'Add an item'));
            }
            $out[] = self::f($field['name'], __((string) ($field['label'] ?? $field['name'])), $type, $extra);
        }

        return $out;
    }

    private static function f(string $name, string $label, string $type = 'text', array $extra = []): array
    {
        return ['name' => $name, 'label' => $label, 'type' => $type] + $extra;
    }

    private static function heading(string $label): array
    {
        return ['name' => '_'.md5($label), 'label' => $label, 'type' => 'heading'];
    }

    private static function header(bool $align = false): array
    {
        $fields = [
            self::f('eyebrow', __('Small title above'), 'text', ['help' => __('E.g. "Our services" — optional.')]),
            self::f('title', __('Section title')),
            self::f('intro', __('Introduction text'), 'textarea'),
        ];
        if ($align) {
            $fields[] = self::f('align', __('Title alignment'), 'select', ['options' => ['left' => __('Left'), 'center' => __('Centered')]]);
        }

        return $fields;
    }

    private static function background(string $default = 'none'): array
    {
        return self::f('background', __('Section background'), 'select', [
            'options' => ['none' => __('Page background'), 'soft' => __('Light section background'), 'strong' => __('Contrasted background')],
            'default' => $default,
        ]);
    }

    private static function tone(string $name = 'tone', string $label = ''): array
    {
        return self::f($name, $label ?: __('Colour'), 'select', ['options' => ['primary' => __('Primary colour'), 'tertiary' => __('Accent colour'), 'secondary' => __('Secondary colour')]]);
    }

    private static function buttons(string $name = 'buttons', string $label = ''): array
    {
        return self::f($name, $label ?: __('Buttons'), 'repeater', [
            'item_label' => 'label',
            'add_label' => __('Add a button'),
            'max_items' => 4,
            'fields' => [
                self::f('label', __('Button text')),
                self::f('url', __('Link'), 'link'),
                self::f('icon', __('Icon'), 'icon'),
                self::f('style', __('Style'), 'select', ['options' => ['primary' => __('Main button ("Buttons" colour)'), 'secondary' => __('Discreet button')]]),
            ],
        ]);
    }

    private static function definitions(): array
    {
        return [

            'hero' => [
                'label' => __('Hero header'),
                'icon' => 'web_asset',
                'description' => __('The top of a page: big title, text, buttons and picture.'),
                'fields' => [
                    self::f('layout', __('Layout'), 'select', ['options' => [
                        'card' => __('Picture in a card with a quote'),
                        'portrait' => __('Framed portrait'),
                        'landscape' => __('Landscape picture with caption'),
                        'simple' => __('Text only'),
                    ]]),
                    self::f('show_breadcrumb', __('Show the breadcrumb (Home / Page)'), 'toggle', ['default' => false]),
                    self::f('badge', __('Label'), 'text', ['help' => __('Small pill above the title.')]),
                    self::f('title', __('Main title'), 'text', ['required' => true]),
                    self::f('subtitle', __('Subtitle (italic, primary colour)')),
                    self::f('text', __('Text'), 'textarea'),
                    self::buttons(),
                    self::f('badges', __('Key points'), 'repeater', [
                        'item_label' => 'text', 'add_label' => __('Add a point'), 'max_items' => 6,
                        'fields' => [self::f('icon', __('Icon'), 'icon'), self::f('title', __('Title (optional)')), self::f('text', __('Text'))],
                    ]),
                    self::f('help_text', __('Help line with the phone number (optional)'), 'text', ['help' => __('E.g. "A question?" — followed by your phone number.')]),
                    self::heading(__('Picture')),
                    self::f('image', __('Picture'), 'image'),
                    self::f('image_icon', __('Caption icon'), 'icon'),
                    self::f('image_title', __('Picture caption (title)')),
                    self::f('image_text', __('Picture caption (text)')),
                    self::f('floating_text', __('Floating label on the picture')),
                    self::heading(__('Quote under the picture ("card" layout)')),
                    self::f('quote', __('Quote'), 'textarea'),
                    self::f('quote_author', __('Author of the quote')),
                    self::f('quote_role', __('Role')),
                ],
            ],

            'page_header' => [
                'label' => __('Simple page title'),
                'icon' => 'title',
                'description' => __('A sober title with a breadcrumb, ideal for text pages.'),
                'fields' => [
                    self::f('title', __('Title'), 'text', ['required' => true]),
                    self::f('text', __('Introduction text'), 'textarea'),
                    self::f('note', __('Discreet note'), 'text', ['help' => __('E.g. "Last updated: September 2026".')]),
                ],
            ],

            'cards' => [
                'label' => __('Cards'),
                'icon' => 'grid_view',
                'description' => __('A grid of cards: services, products, team, values… Very versatile.'),
                'fields' => [
                    ...self::header(true),
                    self::f('style', __('Card style'), 'select', ['options' => [
                        'vertical' => __('Icon on top'),
                        'horizontal' => __('Icon on the left (compact)'),
                        'media' => __('With a picture on top'),
                    ]]),
                    self::f('columns', __('Number of columns (large screens)'), 'select', ['options' => ['3' => __(':count columns', ['count' => 3]), '2' => __(':count columns', ['count' => 2]), '4' => __(':count columns', ['count' => 4])]]),
                    self::background(),
                    self::f('items', __('Cards'), 'repeater', [
                        'item_label' => 'title', 'add_label' => __('Add a card'), 'max_items' => 12,
                        'fields' => [
                            self::f('icon', __('Icon'), 'icon'),
                            self::tone(),
                            self::f('image', __('Picture ("with a picture" style)'), 'image'),
                            self::f('image_badge', __('Label on the picture')),
                            self::f('label', __('Mini title'), 'text', ['help' => __('E.g. "Step 01".')]),
                            self::f('title', __('Title')),
                            self::f('tag', __('Coloured subtitle'), 'text', ['help' => __('E.g. "From €50".')]),
                            self::f('text', __('Text'), 'textarea'),
                            self::f('bullets', __('Bullet list (one line = one point)'), 'textarea'),
                            self::f('note_label', __('Box: title')),
                            self::f('note_text', __('Box: text'), 'textarea'),
                            self::f('footer_icon', __('Card footer: icon'), 'icon'),
                            self::f('footer_text', __('Card footer: text')),
                            self::f('link_label', __('Link: text')),
                            self::f('link_url', __('Link: address'), 'link'),
                        ],
                    ]),
                    self::f('more_label', __('Link under the grid: text')),
                    self::f('more_url', __('Link under the grid: address'), 'link'),
                ],
            ],

            'text_features' => [
                'label' => __('Text + mini cards'),
                'icon' => 'view_quilt',
                'description' => __('A text on the left, and a few small cards on the right (commitments, principles…).'),
                'fields' => [
                    self::f('eyebrow', __('Small title above')),
                    self::f('title', __('Title')),
                    self::f('text', __('Text'), 'rich'),
                    self::f('callout_icon', __('Box: icon'), 'icon'),
                    self::f('callout_title', __('Box: title')),
                    self::f('callout_text', __('Box: text'), 'textarea'),
                    self::f('chip', __('Pill note')),
                    self::buttons(),
                    self::background(),
                    self::heading(__('Right column')),
                    self::f('side_title', __('Title of the right column')),
                    self::f('items', __('Mini cards'), 'repeater', [
                        'item_label' => 'title', 'add_label' => __('Add a mini card'), 'max_items' => 8,
                        'fields' => [self::f('icon', __('Icon'), 'icon'), self::f('title', __('Title')), self::f('text', __('Text'), 'textarea')],
                    ]),
                ],
            ],

            'text_image' => [
                'label' => __('Text + picture'),
                'icon' => 'image',
                'description' => __('A text with a picture, on the left or on the right.'),
                'fields' => [
                    self::f('eyebrow', __('Small title above')),
                    self::f('title', __('Title')),
                    self::f('text', __('Text'), 'rich'),
                    self::f('note_icon', __('Box: icon'), 'icon'),
                    self::f('note_text', __('Box: text'), 'textarea'),
                    self::f('checks', __('Checked points (one line = one point)'), 'textarea'),
                    self::buttons(),
                    self::f('image', __('Picture'), 'image'),
                    self::f('image_side', __('Picture position'), 'select', ['options' => ['right' => __('Right'), 'left' => __('Left')]]),
                    self::f('image_eyebrow', __('Text on the picture: small title')),
                    self::f('image_quote', __('Text on the picture: quote')),
                    self::f('style', __('Presentation'), 'select', ['options' => ['split' => __('Two columns'), 'card' => __('In a card')]]),
                    self::background(),
                ],
            ],

            'steps' => [
                'label' => __('Steps'),
                'icon' => 'format_list_numbered',
                'description' => __('Numbered steps: how it works, an ordering process, a method…'),
                'fields' => [
                    ...self::header(true),
                    self::f('style', __('Style'), 'select', ['options' => ['numbers' => __('Numbered badges'), 'cards' => __('Big numbers')]]),
                    self::f('columns', __('Columns (large screens)'), 'select', ['options' => ['3' => __(':count columns', ['count' => 3]), '4' => __(':count columns', ['count' => 4]), '2' => __(':count columns', ['count' => 2])]]),
                    self::background(),
                    self::f('items', __('Steps'), 'repeater', [
                        'item_label' => 'title', 'add_label' => __('Add a step'), 'max_items' => 8,
                        'fields' => [
                            self::tone(),
                            self::f('icon', __('Icon'), 'icon'),
                            self::f('tag', __('Pill (e.g. "10 min")')),
                            self::f('title', __('Title')),
                            self::f('label', __('Subtitle')),
                            self::f('text', __('Text'), 'textarea'),
                            self::f('footer_icon', __('Footer: icon'), 'icon'),
                            self::f('footer_text', __('Footer: text')),
                        ],
                    ]),
                    self::heading(__('Banner under the steps (optional)')),
                    self::f('bar_icon', __('Icon'), 'icon'),
                    self::f('bar_title', __('Title')),
                    self::f('bar_text', __('Text')),
                    self::f('bar_link_label', __('Link: text')),
                    self::f('bar_link_url', __('Link: address'), 'link'),
                ],
            ],

            'price_banner' => [
                'label' => __('Highlighted price'),
                'icon' => 'payments',
                'description' => __('A price put forward, with an explanation next to it.'),
                'fields' => [
                    self::f('eyebrow', __('Small title')),
                    self::f('price', __('Price')),
                    self::f('unit', __('Precision'), 'text', ['help' => __('E.g. "/ month".')]),
                    self::f('note', __('Note')),
                    self::f('link_label', __('Link: text')),
                    self::f('link_url', __('Link: address'), 'link'),
                    self::f('icon', __('Icon of the text'), 'icon'),
                    self::f('title', __('Title of the text')),
                    self::f('text', __('Text'), 'rich'),
                    self::f('link2_label', __('Second link: text')),
                    self::f('link2_url', __('Second link: address'), 'link'),
                ],
            ],

            'timeline' => [
                'label' => __('Timeline'),
                'icon' => 'timeline',
                'description' => __('A history or a career path: key figures and dated steps.'),
                'fields' => [
                    self::f('eyebrow', __('Small title')),
                    self::f('title', __('Title')),
                    self::f('text', __('Text'), 'textarea'),
                    self::f('stats', __('Key figures'), 'repeater', [
                        'item_label' => 'label', 'add_label' => __('Add a figure'), 'max_items' => 4,
                        'fields' => [self::tone(), self::f('value', __('Value (e.g. "10+")')), self::f('label', __('Label')), self::f('text', __('Precision'))],
                    ]),
                    self::f('quote', __('Quote'), 'textarea'),
                    self::f('items', __('Timeline steps'), 'repeater', [
                        'item_label' => 'title', 'add_label' => __('Add a step'), 'max_items' => 15,
                        'fields' => [
                            self::f('period', __('Period (e.g. "2019 — 2023")')),
                            self::f('place', __('Place / organisation')),
                            self::f('icon', __('Icon'), 'icon'),
                            self::f('title', __('Title')),
                            self::f('subtitle', __('Mention')),
                            self::f('text', __('Description'), 'textarea'),
                            self::f('tags', __('Keywords (separated by commas)')),
                            self::f('highlight', __('Highlight this step'), 'toggle'),
                        ],
                    ]),
                    self::heading(__('Final box (optional)')),
                    self::f('extra_icon', __('Icon'), 'icon'),
                    self::f('extra_title', __('Title')),
                    self::f('extra_items', __('Items'), 'repeater', [
                        'item_label' => 'title', 'add_label' => __('Add an item'), 'max_items' => 9,
                        'fields' => [self::f('title', __('Title')), self::f('text', __('Text'), 'textarea')],
                    ]),
                ],
            ],

            'quote' => [
                'label' => __('Quote'),
                'icon' => 'format_quote',
                'description' => __('A big highlighted quote or testimonial.'),
                'fields' => [
                    self::f('quote', __('Quote'), 'textarea', ['required' => true]),
                    self::f('author', __('Author / signature')),
                    self::background('soft'),
                ],
            ],

            'callout' => [
                'label' => __('Callout'),
                'icon' => 'lightbulb',
                'description' => __('An important message put forward (advice, warning…).'),
                'fields' => [
                    self::f('style', __('Style'), 'select', ['options' => ['soft' => __('Soft (advice, reassurance)'), 'alert' => __('Alert (warning)')]]),
                    self::f('icon', __('Icon'), 'icon'),
                    self::f('eyebrow', __('Small title')),
                    self::f('title', __('Title')),
                    self::f('text', __('Text'), 'rich'),
                    self::f('footer_icon', __('Final sentence: icon'), 'icon'),
                    self::f('footer_text', __('Final sentence')),
                    self::buttons(),
                ],
            ],

            'pricing' => [
                'label' => __('Price list'),
                'icon' => 'sell',
                'description' => __('Pricing cards and practical information (payment, cancellation…).'),
                'fields' => [
                    ...self::header(true),
                    self::background('soft'),
                    self::f('items', __('Prices'), 'repeater', [
                        'item_label' => 'title', 'add_label' => __('Add a price'), 'max_items' => 6,
                        'fields' => [
                            self::f('category', __('Small title (e.g. "Basic")')),
                            self::tone(),
                            self::f('icon', __('Icon'), 'icon'),
                            self::f('title', __('Title')),
                            self::f('price', __('Price (e.g. "€49")')),
                            self::f('unit', __('Precision (e.g. "/ month")')),
                            self::f('text', __('Description'), 'textarea'),
                            self::f('bullets', __('Included (one line = one point)'), 'textarea'),
                            self::f('button_label', __('Button: text')),
                            self::f('button_url', __('Button: link'), 'link'),
                            self::f('highlight', __('Main button (highlighted)'), 'toggle', ['default' => true]),
                        ],
                    ]),
                    self::f('infos', __('Practical information'), 'repeater', [
                        'item_label' => 'title', 'add_label' => __('Add an information'), 'max_items' => 4,
                        'fields' => [self::f('icon', __('Icon'), 'icon'), self::tone(), self::f('title', __('Title')), self::f('text', __('Text'), 'rich')],
                    ]),
                ],
            ],

            'info_columns' => [
                'label' => __('Information columns'),
                'icon' => 'view_column',
                'description' => __('Two or three columns of detailed text.'),
                'fields' => [
                    ...self::header(),
                    self::background(),
                    self::f('columns', __('Columns'), 'repeater', [
                        'item_label' => 'title', 'add_label' => __('Add a column'), 'max_items' => 3,
                        'fields' => [
                            self::f('icon', __('Icon'), 'icon'),
                            self::tone(),
                            self::f('title', __('Title')),
                            self::f('subtitle', __('Subtitle')),
                            self::f('text', __('Text'), 'rich'),
                            self::f('tiles', __('Figures in boxes'), 'repeater', [
                                'item_label' => 'value', 'add_label' => __('Add a figure'), 'max_items' => 4,
                                'fields' => [self::f('label', __('Label')), self::f('value', __('Value')), self::f('text', __('Precision'))],
                            ]),
                            self::f('note', __('Note at the bottom of the column'), 'textarea'),
                            self::f('highlight', __('Highlighted column (card background)'), 'toggle'),
                        ],
                    ]),
                    self::heading(__('List of pills (optional)')),
                    self::f('chips_title', __('Title of the list')),
                    self::f('chips_note', __('Remark')),
                    self::f('chips', __('Pills (one line = one pill)'), 'textarea'),
                ],
            ],

            'faq' => [
                'label' => __('Frequently asked questions'),
                'icon' => 'quiz',
                'description' => __('A list of questions / answers, expandable or always visible.'),
                'fields' => [
                    ...self::header(),
                    self::f('style', __('Presentation'), 'select', ['options' => ['accordion' => __('Expandable answers'), 'list' => __('Always visible answers')]]),
                    self::background('soft'),
                    self::f('items', __('Questions'), 'repeater', [
                        'item_label' => 'question', 'add_label' => __('Add a question'), 'max_items' => 30,
                        'fields' => [self::f('icon', __('Icon ("always visible" presentation)'), 'icon'), self::f('question', __('Question')), self::f('answer', __('Answer'), 'rich')],
                    ]),
                ],
            ],

            'gallery' => [
                'label' => __('Picture gallery'),
                'icon' => 'photo_library',
                'description' => __('A grid of pictures with optional captions: achievements, products, events…'),
                'fields' => [
                    ...self::header(true),
                    self::f('columns', __('Number of columns (large screens)'), 'select', ['options' => ['3' => __(':count columns', ['count' => 3]), '2' => __(':count columns', ['count' => 2]), '4' => __(':count columns', ['count' => 4])]]),
                    self::f('ratio', __('Picture format'), 'select', ['options' => ['landscape' => __('Landscape (4:3)'), 'square' => __('Square'), 'portrait' => __('Portrait (3:4)')]]),
                    self::background(),
                    self::f('items', __('Pictures'), 'repeater', [
                        'item_label' => 'caption', 'add_label' => __('Add a picture'), 'max_items' => 40,
                        'fields' => [self::f('image', __('Picture'), 'image'), self::f('caption', __('Caption')), self::f('url', __('Link (optional)'), 'link')],
                    ]),
                ],
            ],

            'video' => [
                'label' => __('Video'),
                'icon' => 'smart_display',
                'description' => __('A YouTube or Vimeo video, loaded only when the visitor clicks on it.'),
                'fields' => [
                    ...self::header(true),
                    self::f('url', __('Video address'), 'text', ['required' => true, 'help' => __('E.g. https://www.youtube.com/watch?v=… or https://vimeo.com/…')]),
                    self::f('poster', __('Cover picture (optional)'), 'image'),
                    self::f('caption', __('Caption')),
                    self::background(),
                ],
            ],

            'cta' => [
                'label' => __('Call to action'),
                'icon' => 'ads_click',
                'description' => __('A banner that invites visitors to contact you, book, buy…'),
                'fields' => [
                    self::f('style', __('Presentation'), 'select', ['options' => [
                        'banner' => __('Big coloured banner'),
                        'centered' => __('Centered'),
                        'split' => __('With an information card on the right'),
                    ]]),
                    self::f('icon', __('Icon'), 'icon'),
                    self::f('badge', __('Label')),
                    self::f('title', __('Title'), 'text', ['required' => true]),
                    self::f('text', __('Text'), 'textarea'),
                    self::buttons(),
                    self::f('show_contact', __('Show phone and address'), 'toggle', ['default' => false]),
                    self::f('show_hours', __('Show the opening hours'), 'toggle'),
                    self::f('links', __('Secondary links'), 'repeater', [
                        'item_label' => 'label', 'add_label' => __('Add a link'), 'max_items' => 4,
                        'fields' => [self::f('icon', __('Icon'), 'icon'), self::f('label', __('Text')), self::f('url', __('Link'), 'link')],
                    ]),
                    self::heading(__('Information card ("with a card" presentation)')),
                    self::f('side_title', __('Title')),
                    self::f('side_items', __('Lines'), 'repeater', [
                        'item_label' => 'text', 'add_label' => __('Add a line'), 'max_items' => 6,
                        'fields' => [self::f('icon', __('Icon'), 'icon'), self::f('text', __('Text'), 'textarea')],
                    ]),
                    self::f('side_link_label', __('Link: text')),
                    self::f('side_link_url', __('Link: address'), 'link'),
                ],
            ],

            'contact_cards' => [
                'label' => __('Contact cards'),
                'icon' => 'contact_page',
                'description' => __('Address, opening hours, phone/e-mail and a free card — filled automatically from "Site information".'),
                'fields' => [
                    self::f('address_text', __('Address card: text'), 'textarea'),
                    self::f('address_footer', __('Address card: footer')),
                    self::f('hours_footer', __('Hours card: footer')),
                    self::f('phone_text', __('Phone card: text'), 'textarea'),
                    self::f('phone_footer', __('Phone card: footer')),
                    self::heading(__('Fourth card (optional)')),
                    self::f('extra_icon', __('Icon'), 'icon'),
                    self::f('extra_title', __('Title')),
                    self::f('extra_text', __('Text'), 'textarea'),
                    self::f('extra_footer', __('Footer')),
                ],
            ],

            'map' => [
                'label' => __('Map & directions'),
                'icon' => 'map',
                'description' => __('A map of your location, directions and transport — with the contact form next to it if you wish.'),
                'fields' => [
                    self::f('eyebrow', __('Small title')),
                    self::f('title', __('Title')),
                    self::f('arrival_title', __('Arrival instructions: title')),
                    self::f('arrival_text', __('Arrival instructions: text'), 'textarea'),
                    self::f('transports_title', __('Transport: title')),
                    self::f('transports', __('Transport'), 'repeater', [
                        'item_label' => 'title', 'add_label' => __('Add a means of transport'), 'max_items' => 6,
                        'fields' => [
                            self::f('badge', __('Letter or number (M, 12, P…)')),
                            self::f('tone', __('Colour of the badge'), 'select', ['options' => ['primary' => __('Primary colour'), 'error' => __('Red'), 'tertiary' => __('Accent colour'), 'secondary' => __('Secondary colour')]]),
                            self::f('title', __('Title')),
                            self::f('lines', __('Details (one line = one stop)'), 'textarea'),
                        ],
                    ]),
                    self::f('show_form', __('Show the contact form on the right'), 'toggle', ['default' => false]),
                    self::heading(__('Form (if shown)')),
                    self::f('form_eyebrow', __('Small title')),
                    self::f('form_title', __('Title')),
                    self::f('form_text', __('Text'), 'textarea'),
                    self::f('form_warning', __('Confidentiality notice'), 'textarea'),
                    self::f('form_subjects', __('Subjects offered in the list (one line = one choice)'), 'textarea'),
                ],
            ],

            'contact_form' => [
                'label' => __('Contact form'),
                'icon' => 'contact_mail',
                'description' => __('The form to write to you. Messages arrive in "Messages".'),
                'fields' => [
                    self::f('eyebrow', __('Small title')),
                    self::f('title', __('Title')),
                    self::f('text', __('Text'), 'textarea'),
                    self::f('form_warning', __('Confidentiality notice'), 'textarea'),
                    self::f('form_subjects', __('Subjects offered in the list (one line = one choice)'), 'textarea'),
                    self::f('ask_phone', __('Ask for a phone number'), 'toggle', ['default' => true]),
                    self::background(),
                ],
            ],

            'rich_text' => [
                'label' => __('Free text'),
                'icon' => 'article',
                'description' => __('A formatted text (headings, lists, links): legal notice, article…'),
                'fields' => [
                    self::f('eyebrow', __('Small title')),
                    self::f('title', __('Title')),
                    self::f('content', __('Content'), 'rich'),
                    self::buttons(),
                    self::f('width', __('Width'), 'select', ['options' => ['narrow' => __('Narrow (comfortable reading)'), 'wide' => __('Wide')]]),
                    self::background(),
                ],
            ],

        ];
    }
}
