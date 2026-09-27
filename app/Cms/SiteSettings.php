<?php

namespace App\Cms;

use App\Models\Media;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Central information about the site ("Site information" in the administration).
 * Every value with a "tag" can be reused in any text: {phone}, {address}…
 *
 * Fields marked "translatable" can have a different value for each language of
 * the site (stored in the "translations" setting).
 */
class SiteSettings
{
    private const CACHE_KEY = 'mycms.settings';

    private ?array $values = null;

    /** @var string[]|null */
    private static ?array $translatable = null;

    /** Language of the public content being rendered (null = default language) */
    private ?string $contentLocale = null;

    /**
     * Groups and fields, in the order of the administration.
     * Types: text, textarea, email, tel, url, link, toggle, media, hours, links, number, date
     */
    public static function groups(): array
    {
        return [
            'identity' => [
                'label' => __('Identity'),
                'icon' => 'badge',
                'intro' => __('The name of your site, as shown in the header, the footer and the browser tab.'),
                'fields' => [
                    'site_name' => ['label' => __('Site name'), 'type' => 'text', 'default' => 'My Website', 'tag' => 'site_name', 'required' => true, 'translatable' => true, 'help' => __('Your name, your company, your association…')],
                    'tagline' => ['label' => __('Tagline'), 'type' => 'text', 'default' => 'Welcome to my website', 'tag' => 'tagline', 'translatable' => true, 'help' => __('Shown under the name in the header.')],
                    'initials' => ['label' => __('Initials (round logo)'), 'type' => 'text', 'default' => 'MW', 'max' => 3, 'help' => __('2 or 3 letters shown in the logo badge when no logo is chosen.')],
                    'logo_id' => ['label' => __('Logo (optional)'), 'type' => 'media', 'default' => null, 'help' => __('Replaces the badge with your initials.')],
                    'favicon_id' => ['label' => __('Browser tab icon (optional)'), 'type' => 'media', 'default' => null, 'help' => __('A small square image (PNG), at least 64 × 64 pixels.')],
                ],
            ],
            'contact' => [
                'label' => __('Contact details'),
                'icon' => 'location_on',
                'intro' => __('Address, phone and e-mail: change them here and they are updated everywhere on the site.'),
                'fields' => [
                    'phone' => ['label' => __('Phone'), 'type' => 'tel', 'default' => '', 'tag' => 'phone'],
                    'email' => ['label' => __('Contact e-mail'), 'type' => 'email', 'default' => 'contact@example.com', 'tag' => 'email', 'required' => true],
                    'address_street' => ['label' => __('Street address'), 'type' => 'text', 'default' => '', 'tag' => 'street'],
                    'postal_code' => ['label' => __('Postal code'), 'type' => 'text', 'default' => '', 'tag' => 'postal_code'],
                    'city' => ['label' => __('City'), 'type' => 'text', 'default' => '', 'tag' => 'city'],
                    'country' => ['label' => __('Country'), 'type' => 'text', 'default' => '', 'tag' => 'country', 'translatable' => true],
                    'address_details' => ['label' => __('Access details'), 'type' => 'text', 'default' => '', 'tag' => 'access', 'translatable' => true, 'help' => __('Building, floor, door code…')],
                    'show_contact_in_header' => ['label' => __('Show the phone number in the header'), 'type' => 'toggle', 'default' => true],
                    'map_lat' => ['label' => __('Latitude (map)'), 'type' => 'number', 'default' => '', 'help' => __('On openstreetmap.org: right-click on your address → "Show address" gives the coordinates.')],
                    'map_lng' => ['label' => __('Longitude (map)'), 'type' => 'number', 'default' => ''],
                ],
            ],
            'hours' => [
                'label' => __('Opening hours'),
                'icon' => 'schedule',
                'intro' => __('Shown in the footer and in the contact sections. Leave empty if not relevant.'),
                'fields' => [
                    'hours' => ['label' => __('Opening hours'), 'type' => 'hours', 'default' => []],
                    'hours_note' => ['label' => __('Note'), 'type' => 'text', 'default' => '', 'tag' => 'hours_note', 'translatable' => true, 'help' => __('E.g. "By appointment only".')],
                ],
            ],
            'button' => [
                'label' => __('Main button'),
                'icon' => 'ads_click',
                'intro' => __('The button in the header of every page: contact, booking, shop, donation…'),
                'fields' => [
                    'cta_label' => ['label' => __('Button text'), 'type' => 'text', 'default' => 'Contact us', 'tag' => 'button_text', 'translatable' => true, 'max' => 40, 'help' => __('Leave empty to hide the button.')],
                    'cta_url' => ['label' => __('Button link'), 'type' => 'link', 'default' => '/contact', 'translatable' => true, 'help' => __('One of your pages (/contact), a web address (https://…), {phone} or {email}. Usable in your texts as the {button} link.')],
                    'cta_icon' => ['label' => __('Button icon'), 'type' => 'icon', 'default' => 'mail'],
                ],
            ],
            'social' => [
                'label' => __('Social networks'),
                'icon' => 'share',
                'intro' => __('Links shown in the footer (Facebook, Instagram, LinkedIn…).'),
                'fields' => [
                    'social_links' => ['label' => __('Links'), 'type' => 'links', 'default' => []],
                ],
            ],
            'announcement' => [
                'label' => __('Announcement'),
                'icon' => 'campaign',
                'intro' => __('A banner at the top of every page: holidays, exceptional closing, news…'),
                'fields' => [
                    'announcement_enabled' => ['label' => __('Show the banner'), 'type' => 'toggle', 'default' => false],
                    'announcement_text' => ['label' => __('Banner text'), 'type' => 'text', 'default' => '', 'translatable' => true],
                    'announcement_url' => ['label' => __('Link (optional)'), 'type' => 'link', 'default' => '', 'translatable' => true],
                    'announcement_start' => ['label' => __('From (optional)'), 'type' => 'date', 'default' => ''],
                    'announcement_end' => ['label' => __('Until (optional)'), 'type' => 'date', 'default' => '', 'help' => __('The banner disappears automatically after this date.')],
                ],
            ],
            'footer' => [
                'label' => __('Footer'),
                'icon' => 'web',
                'intro' => __('What is shown at the bottom of every page.'),
                'fields' => [
                    'footer_about' => ['label' => __('Short presentation'), 'type' => 'textarea', 'default' => '', 'translatable' => true],
                    'footer_notice_enabled' => ['label' => __('Show a notice above the footer'), 'type' => 'toggle', 'default' => false],
                    'footer_notice_text' => ['label' => __('Notice text'), 'type' => 'textarea', 'default' => '', 'translatable' => true, 'help' => __('Important information shown on every page (emergency number, legal notice…).')],
                    'footer_legal_line' => ['label' => __('Additional legal line'), 'type' => 'text', 'default' => '', 'translatable' => true, 'help' => __('Shown at the very bottom, e.g. a professional registration or a protected title.')],
                    'show_credit' => ['label' => __('Show "Made with MyCMS"'), 'type' => 'toggle', 'default' => true],
                ],
            ],
            'contact_form' => [
                'label' => __('Contact form'),
                'icon' => 'mail',
                'intro' => __('Where and how you are notified of new messages.'),
                'fields' => [
                    'contact_recipient' => ['label' => __('Send notifications to this address'), 'type' => 'email', 'default' => '', 'help' => __('Empty = the contact e-mail.')],
                    'contact_notify' => ['label' => __('Send me an e-mail for each new message'), 'type' => 'toggle', 'default' => true],
                    'contact_include_content' => ['label' => __('Include the message in the e-mail'), 'type' => 'toggle', 'default' => false, 'help' => __('Off by default: e-mail is not a confidential channel. You then read the message in the administration.')],
                    'privacy_url' => ['label' => __('Privacy policy page'), 'type' => 'link', 'default' => '/privacy-policy', 'translatable' => true, 'help' => __('Linked from the consent checkbox of the form.')],
                ],
            ],
            'company' => [
                'label' => __('Legal information'),
                'icon' => 'gavel',
                'intro' => __('Used in the "Legal notice" page (tags {company_name}, {host_name}…). Fill in what applies to you.'),
                'fields' => [
                    'company_name' => ['label' => __('Legal name'), 'type' => 'text', 'default' => '', 'tag' => 'company_name'],
                    'legal_status' => ['label' => __('Legal status'), 'type' => 'text', 'default' => '', 'tag' => 'legal_status', 'translatable' => true, 'help' => __('Sole proprietorship, company, association…')],
                    'registration_number' => ['label' => __('Registration number'), 'type' => 'text', 'default' => '', 'tag' => 'registration_number', 'help' => __('Company number, SIRET, association number…')],
                    'vat_number' => ['label' => __('VAT number'), 'type' => 'text', 'default' => '', 'tag' => 'vat_number'],
                    'publication_director' => ['label' => __('Publication director'), 'type' => 'text', 'default' => '', 'tag' => 'publication_director'],
                    'host_name' => ['label' => __('Hosting provider (name)'), 'type' => 'text', 'default' => '', 'tag' => 'host_name'],
                    'host_address' => ['label' => __('Hosting provider (address)'), 'type' => 'text', 'default' => '', 'tag' => 'host_address'],
                ],
            ],
            'seo' => [
                'label' => __('Search engines'),
                'icon' => 'travel_explore',
                'intro' => __('What Google and social networks show about your site.'),
                'fields' => [
                    'seo_suffix' => ['label' => __('Page title suffix'), 'type' => 'text', 'default' => '', 'translatable' => true, 'help' => __('Added after the title of each page. Empty = the site name.')],
                    'seo_description' => ['label' => __('Default description'), 'type' => 'textarea', 'default' => '', 'max' => 300, 'translatable' => true],
                    'og_image_id' => ['label' => __('Sharing image'), 'type' => 'media', 'default' => null, 'help' => __('Image shown when a link to the site is shared (Facebook, WhatsApp…).')],
                    'allow_indexing' => ['label' => __('Allow search engines to index the site'), 'type' => 'toggle', 'default' => false, 'help' => __('Turn it on when your site is ready to go public.')],
                ],
            ],
        ];
    }

    /** @return array<string, array> All fields, by key */
    public static function fields(): array
    {
        return collect(static::groups())->flatMap(fn ($g) => $g['fields'])->all();
    }

    /** @return string[] Keys of the fields that can be translated */
    public static function translatableKeys(): array
    {
        if (self::$translatable === null) {
            self::$translatable = []; // guard: the labels are translated while computing
            self::$translatable = array_keys(array_filter(static::fields(), fn ($f) => $f['translatable'] ?? false));
        }

        return self::$translatable;
    }

    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        // Before installation (no database yet), default values are used
        try {
            $stored = Cache::get(self::CACHE_KEY);
            if (! is_array($stored)) {
                $stored = Schema::hasTable('settings') ? Setting::query()->pluck('value', 'key')->all() : null;
                $stored === null || Cache::forever(self::CACHE_KEY, $stored);
            }
        } catch (\Throwable) {
            $stored = null;
        }

        // Stored values are available right away: computing the defaults
        // translates the labels, which needs the active theme (a setting).
        $this->values = $stored ?? [];
        $defaults = collect(static::fields())->map(fn ($f) => $f['default'] ?? null)->all();

        return $this->values = array_replace($defaults, $stored ?? []);
    }

    /**
     * A setting. Translatable fields return the value of the content language
     * being rendered when one is set.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->contentLocale && in_array($key, static::translatableKeys(), true)) {
            $translated = $this->all()['translations'][$this->contentLocale][$key] ?? null;
            if ($translated !== null && $translated !== '') {
                return $translated;
            }
        }

        return Arr::get($this->all(), $key, $default);
    }

    /** Value stored for a language (without fallback), for the administration. */
    public function translation(string $locale, string $key): mixed
    {
        return $this->all()['translations'][$locale][$key] ?? null;
    }

    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->flush();
    }

    /** @param  array<string, mixed>  $values */
    public function setTranslations(string $locale, array $values): void
    {
        $translations = (array) ($this->all()['translations'] ?? []);
        $translations[$locale] = array_filter(
            array_replace((array) ($translations[$locale] ?? []), $values),
            fn ($v) => $v !== null && $v !== ''
        );
        $this->set(['translations' => $translations]);
    }

    public function flush(): void
    {
        rescue(fn () => Cache::forget(self::CACHE_KEY), report: false);
        $this->values = null;
        app(Placeholders::class)->flush();
    }

    public function setContentLocale(?string $locale): void
    {
        $this->contentLocale = $locale;
        app(Placeholders::class)->flush();
    }

    public function contentLocale(): ?string
    {
        return $this->contentLocale;
    }

    /* ---------- Shortcuts used by the templates ---------- */

    public function siteName(): string
    {
        return (string) $this->get('site_name');
    }

    public function fullAddress(): string
    {
        $cityLine = trim($this->get('postal_code').' '.$this->get('city'));

        return implode(', ', array_filter([trim((string) $this->get('address_street')), $cityLine, trim((string) $this->get('country'))]));
    }

    public function phoneHref(): string
    {
        return 'tel:'.preg_replace('/[^0-9+]/', '', (string) $this->get('phone'));
    }

    public function media(string $key): ?Media
    {
        $id = $this->get($key);

        return $id ? Media::find($id) : null;
    }

    public function announcementActive(): bool
    {
        if (! $this->get('announcement_enabled') || ! $this->get('announcement_text')) {
            return false;
        }
        $today = now()->toDateString();
        $start = $this->get('announcement_start');
        $end = $this->get('announcement_end');

        return (! $start || $today >= $start) && (! $end || $today <= $end);
    }

    public function hasMap(): bool
    {
        return (float) $this->get('map_lat') && (float) $this->get('map_lng');
    }

    public function mapEmbedUrl(): ?string
    {
        if (! $this->hasMap()) {
            return null;
        }
        $lat = (float) $this->get('map_lat');
        $lng = (float) $this->get('map_lng');
        $bbox = implode('%2C', [round($lng - 0.005, 4), round($lat - 0.0022, 4), round($lng + 0.005, 4), round($lat + 0.0022, 4)]);

        return "https://www.openstreetmap.org/export/embed.html?bbox={$bbox}&layer=mapnik&marker={$lat}%2C{$lng}";
    }

    /** @return array<int, array{label: string, value: string}> */
    public function hours(): array
    {
        return array_values(array_filter((array) $this->get('hours', []), fn ($row) => is_array($row) && (($row['label'] ?? '') !== '' || ($row['value'] ?? '') !== '')));
    }

    /** @return array<int, array{label: string, url: string}> */
    public function socialLinks(): array
    {
        return array_values(array_filter((array) $this->get('social_links', []), fn ($row) => is_array($row) && ! empty($row['url']) && ! empty($row['label'])));
    }
}
