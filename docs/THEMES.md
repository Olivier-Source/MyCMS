# Creating a theme for MyCMS

*[Version française](fr/THEMES.md)*

A theme changes **the whole look of the public site** (the administration never
changes). Switching themes never touches the content: pages, sections, texts
and pictures stay the same.

MyCMS ships with two themes you can read as examples:

| Theme | Folder | What it shows |
|---|---|---|
| **MyCMS Default** | `resources/themes/default` | A complete theme: every template, fonts, options, palettes. |
| **Minimal** | `resources/themes/minimal` | A **child theme**: it only redefines the header, the footer and the hero, adds its own palettes, options and a custom section ("Key figures"). |

The easiest way to start is to **copy `minimal`** and change it.

---

## 1. Prerequisites

To *create* a theme you need:

- to know **HTML and CSS**; templates use [Blade](https://laravel.com/docs/blade), Laravel's
  template language (`{{ $variable }}`, `@if`, `@foreach`…) — the basics are learned in an hour;
- a MyCMS installed locally to try your theme (see the [README](../README.md));
- *optional:* Node.js 20+ if you want to build your CSS with Tailwind CSS like the bundled themes.
  Any other tool works: MyCMS only loads the **final** CSS and JS files listed in `theme.json`.

To *install* a theme, people only need the administration (Themes → Install a
theme) or the command `php artisan mycms:theme install <address>`: no build tool,
no Node.js on the server.

---

## 2. Structure of a theme

```
my-theme/
├── theme.json              ← required: the manifest
├── screenshot.png          ← preview shown in the administration (svg, png, jpg, webp)
├── views/                  ← required: Blade templates
│   ├── layouts/site.blade.php
│   ├── partials/footer.blade.php
│   ├── site/page.blade.php
│   ├── blocks/hero.blade.php … (one file per section type)
│   └── errors/404.blade.php
├── assets/                 ← built CSS/JS, fonts, pictures (served publicly)
│   ├── theme.css
│   └── theme.js
└── lang/                   ← optional: translations of the theme's own texts
    └── fr.json
```

A theme **only needs the templates it changes**: every template missing from your
theme is taken from its **parent theme** (`"parent"` in `theme.json`), then from the
**default theme**. A child theme can therefore be three files.

Files copied at installation: `*.blade.php`, `*.json`, `*.css`, `*.js`, `*.map`,
fonts (`woff`, `woff2`, `ttf`, `otf`), pictures (`png`, `jpg`, `jpeg`, `webp`, `gif`,
`svg`, `ico`, `avif`), `*.md`, `*.txt`, `LICENSE`, `README`. Everything else
(`.git`, `node_modules`, other PHP files, build configs…) is ignored.

Only these extensions are served publicly from a theme folder:
`css js woff woff2 ttf otf png jpg jpeg webp gif svg ico avif`
(address: `/themes/{slug}/{path}`). Templates and `theme.json` are never served.

---

## 3. `theme.json`

```json
{
    "name": "My theme",
    "slug": "my-theme",
    "version": "1.0.0",
    "description": "A short description shown in the administration.",
    "author": "Your name",
    "homepage": "https://github.com/you/mycms-theme-my-theme",
    "license": "MIT",
    "parent": "default",
    "screenshot": "screenshot.png",
    "assets": {
        "css": ["assets/theme.css"],
        "js": ["assets/theme.js"]
    },
    "default_palette": "sea",
    "palettes": {
        "sea": {
            "name": "Sea",
            "colors": {
                "primary": "#1d4e89", "secondary": "#5c6b73", "accent": "#e07a5f", "button": "#1d4e89",
                "background": "#ffffff", "section": "#f1f5f9", "card": "#ffffff",
                "text": "#1b2631", "textMuted": "#4b5563"
            }
        }
    },
    "options": [],
    "blocks": {}
}
```

| Key | Required | Description |
|---|---|---|
| `name` | yes | Name shown in the administration (translatable through `lang/*.json`). |
| `slug` | yes | Identifier: lowercase letters, digits and dashes (`my-theme`). Must be unique. `default` and `minimal` are reserved. |
| `version` | no | Shown in the administration. Use [semantic versioning](https://semver.org). |
| `description`, `author`, `license` | no | Shown in the administration. |
| `homepage` | no | `https://` link shown in the administration. |
| `parent` | no | Slug of the theme to inherit missing templates from (usually `default`). |
| `screenshot` | no | Preview picture (default: `screenshot.png`). Recommended size 1600 × 1000. |
| `assets.css`, `assets.js` | no | Files loaded on every page, relative to the theme folder. |
| `palettes` | no | Ready-made colour palettes offered in **Colours & style** (see §6). |
| `default_palette` | no | Palette applied the first time the theme is activated. |
| `options` | no | Settings offered to the user in **Colours & style → Theme options** (see §7). |
| `blocks` | no | New section types added by the theme (see §8). |

---

## 4. Templates

All public templates live in the **`theme::`** view namespace. MyCMS renders a page with
`theme::site.page`, which extends `theme::layouts.site`, and includes one
`theme::blocks.{type}` per section.

| Template | Role |
|---|---|
| `layouts/site.blade.php` | The HTML page: `<head>`, header, menu, `@yield('content')`, footer. |
| `site/page.blade.php` | Loops over the sections of the page (rarely redefined). |
| `partials/footer.blade.php` | Footer (included by the default layout). |
| `blocks/{type}.blade.php` | One template per section type (see the list below). |
| `blocks/partials/*.blade.php` | Pieces shared by the sections: `heading`, `buttons`, `breadcrumb`, `contact-form`, `cta-extras`. |
| `errors/404.blade.php`, `errors/error.blade.php` | Error pages ("page not found", 403, 419, 429). |

Section types of MyCMS: `hero`, `page_header`, `cards`, `text_features`, `text_image`,
`steps`, `price_banner`, `timeline`, `quote`, `callout`, `pricing`, `info_columns`,
`faq`, `gallery`, `video`, `cta`, `contact_cards`, `map`, `contact_form`, `rich_text`.
Their fields are described in `app/Cms/BlockRegistry.php`; read the default theme's
template of a section before redefining it.

### Variables available in every template

| Variable | Content |
|---|---|
| `$settings` | Site information: `$settings->get('phone')`, `siteName()`, `fullAddress()`, `phoneHref()`, `hours()`, `socialLinks()`, `announcementActive()`, `mapEmbedUrl()`… Keys are listed in `app/Cms/SiteSettings.php`. |
| `$ph` | Tag replacement: `$ph->text($text)` (safe HTML), `$ph->raw($text)` (for attributes). |
| `$r` | Rendering helpers, see below. |
| `$theme` | The active theme: `$theme->styles()`, `$theme->scripts()`, `$theme->assetUrl('img/logo.svg')`. |
| `$page` | The current page (`title`, `url()`, `path()`, `is_home`…) — `null` on error pages. |
| `$navPages`, `$footerPages` | Pages of the main menu and of the footer (current language). |
| `$homeUrl` | Address of the home page in the current language. |
| `$ctaLabel`, `$ctaUrl` | Main button of the header (empty when disabled). |
| `$language`, `$languages`, `$contentLocale` | Current language (`htmlLang()`, `direction()`, `nativeName()`), all languages, current code. |
| `$alternates` | `[code => url]` of the same page in the other languages (language menu, `hreflang`). |
| `$preview` | `true` when the page is previewed from the administration. |
| `$cspNonce` | Nonce to put on your inline `<style>` / `<script>` tags. |

In a section template you also get **`$d`** (the data of the section, every field
always present) and **`$block`** (the model).

### Rendering helpers (`$r`)

| Helper | Use |
|---|---|
| `{!! $r->t($d['title']) !!}` | Plain text → safe HTML (tags like `{phone}` replaced, line breaks kept). **Use it for every text field.** |
| `{!! $r->h($d['text']) !!}` | Formatted text ("rich" fields), already sanitised when saved. |
| `{!! $r->quote($d['quote']) !!}` | Text between the quotation marks of the current language. |
| `$r->img($d['image'])` | Picture (`url()`, `alt`, `width`, `height`) or `null`. |
| `{!! $r->linkAttrs($d['url']) !!}` | `href="…"` (+ `target="_blank"` for external links). Handles `{phone}`, `{email}`, `{button}` and the language prefix. |
| `$r->href($url)` | The address only. |
| `$r->lines($text)` | Lines of a multi-line field (bullet lists). |
| `$r->background($d['background'])`, `$r->tone($tone, 'soft')`, `$r->columns($n)` | Ready-made Tailwind classes used by the default theme. |
| `$r->videoEmbed($url)` | Privacy-friendly YouTube / Vimeo embed address, or `null`. |

Other tools: `<x-icon name="call" class="text-[20px]" />` inserts one of the icons of
`resources/icons` (Material Symbols); `theme_option('name')` reads a theme option;
`__('Text')` translates a text (see §9).

> **Security** — Never output a field with `{!! $d['…'] !!}` directly: always go
> through `$r->t()`, `$r->h()` or `{{ }}`. Text fields are plain text; only "rich"
> fields contain (sanitised) HTML.

---

## 5. Styles, scripts and the security policy

The public site sends a strict **Content-Security-Policy**:

- scripts: files of your theme, or inline `<script nonce="{{ $cspNonce }}">`;
- styles: files of your theme, or inline `<style nonce="{{ $cspNonce }}">`;
  **`style="…"` attributes are blocked**;
- fonts and pictures: your theme, the site, `data:` URIs;
- no external resource (Google Fonts, CDNs…): **host your fonts in the theme**;
- iframes: only OpenStreetMap, YouTube (nocookie) and Vimeo.

List your files in `assets.css` / `assets.js`; the layout loads them with
`$theme->styles()` / `$theme->scripts()`, with a version number that changes with the
file (browser caches are refreshed automatically).

### Building with Tailwind CSS (like the bundled themes)

The bundled themes are built by `vite.theme.config.js`:

```bash
npx vite build -c vite.theme.config.js --mode my-theme   # builds resources/themes/my-theme
```

Your `theme.js` imports your `theme.css`; `@source` lines tell Tailwind where to find
the classes (your templates, and those of the parent theme):

```css
@import 'tailwindcss';
@import './palette.css';      /* copy it from the default theme */
@source './views';
@source '../default/views';   /* child theme: classes of the inherited templates */
@source '../../../app/Cms';
```

For a theme developed in its **own repository**, a minimal `package.json`
(`vite`, `tailwindcss`, `@tailwindcss/vite`) and the same Vite configuration are enough.
**Commit the built `assets/` folder**: sites install themes without building them.

---

## 6. Colours

The user chooses 9 colours in **Colours & style** (or one of your palettes). MyCMS
derives every shade and exposes them as CSS variables `--c-{name}` (`r g b` values):

`primary`, `on-primary`, `primary-container`, `primary-fixed`, `on-primary-fixed`…,
`secondary…`, `tertiary…` (accent colour), `btn`, `on-btn`, `btn-hover`,
`surface`, `surface-container-lowest|low|(none)|high|highest`, `on-surface`,
`on-surface-variant`, `outline-variant`, `error…`.

`palette.css` (in the default theme) maps them to Tailwind colours: `bg-primary`,
`text-on-surface`, `bg-btn`, `bg-surface-container-low`… Without Tailwind, use them
directly: `color: rgb(var(--c-primary));`.

Always pair a background with its `on-` colour (`bg-btn text-on-btn`): it is computed to
stay readable whatever the user chooses, including dark palettes.

---

## 7. Options

Options appear in **Colours & style → Theme options**:

```json
"options": [
    { "name": "header_layout", "label": "Header layout", "type": "select",
      "options": { "centered": "Centered", "inline": "Inline" }, "default": "centered" },
    { "name": "sticky_header", "label": "Sticky menu", "type": "toggle", "default": true },
    { "name": "hero_image", "label": "Background picture", "type": "media" },
    { "name": "accent", "label": "Extra colour", "type": "color", "default": "#ff6600" },
    { "name": "footer_note", "label": "Footer note", "type": "text", "help": "Shown under the footer." }
]
```

Types: `text`, `textarea`, `toggle`, `select`, `color`, `media` (id of a picture:
`$r->img(theme_option('hero_image'))`). Read them with `theme_option('header_layout')`.
Values are stored per theme: switching back to a theme restores its options.

---

## 8. Custom sections

A theme can add section types. Declare the fields in `theme.json` and create
`views/blocks/{type}.blade.php`:

```json
"blocks": {
    "stats": {
        "label": "Key figures",
        "icon": "trending_up",
        "description": "A few big figures in a row.",
        "fields": [
            { "name": "title", "label": "Title", "type": "text" },
            { "name": "items", "label": "Figures", "type": "repeater", "item_label": "label",
              "add_label": "Add a figure", "max_items": 4,
              "fields": [
                  { "name": "value", "label": "Value", "type": "text" },
                  { "name": "label", "label": "Label", "type": "text" }
              ] },
            { "name": "dark", "label": "Dark background", "type": "toggle", "default": false }
        ]
    }
}
```

- `type` (section): lowercase letters, digits, `_`; `icon`: a name from `resources/icons`.
- Field types: `text`, `textarea`, `rich`, `image`, `icon`, `select` (with `options`),
  `toggle`, `link`, `number`, `repeater` (with `fields`, 2 levels max), `heading`.
- Field keys: `name`, `label`, `type`, and optionally `default`, `required`, `max`,
  `help`, `max_items`, `item_label`, `add_label`.

The administration form, validation and sanitising are generated automatically. When
another theme is activated, the section stays in the database but is not shown (the
administration says "not available with the current theme").

---

## 9. Translations

Write your theme's texts **in English** with `__('Text')`, and add
`lang/{locale}.json` files:

```json
{ "Read more": "Lire la suite", "Header layout": "Disposition de l'en-tête" }
```

The labels of `theme.json` (name, description, options, sections) are translated with
the same files. Strings already translated by MyCMS (`__('Contact')`…) don't need to be
repeated. Details: [LANGUAGES.md](LANGUAGES.md).

---

## 10. Publishing and installing

1. Put the theme in a **public Git repository**, `theme.json` at the root
   (GitHub, GitLab, Codeberg…), with the built `assets/` folder committed.
2. Tag your versions (`git tag v1.0.0 && git push --tags`).
3. People install it from **Themes → Install a theme**:
   - `https://github.com/you/mycms-theme-my-theme` (default branch),
   - `https://github.com/you/mycms-theme-my-theme#v1.0.0` (a tag or a branch),
   - a link to a `.zip`, or an uploaded `.zip` file (`theme.json` at the root or in a
     single top-level folder, as in GitHub archives).
4. Or from the command line:
   ```bash
   php artisan mycms:theme install https://github.com/you/mycms-theme-my-theme --activate
   php artisan mycms:theme update my-theme
   php artisan mycms:theme list
   ```

Installed themes are stored in `storage/app/themes/{slug}`. Themes installed from an
address can be **updated** in one click.

> **Trust** — A theme contains Blade templates, i.e. PHP code executed by the server.
> Only install themes from sources you trust. Server administrators can forbid
> installing from the administration with `MYCMS_ALLOW_PACKAGE_INSTALL=false`.

---

## 11. Checklist before publishing

- [ ] `theme.json` is valid JSON, with a unique `slug` and a `version`.
- [ ] Every page type works: home, text page, contact page (form sent / with errors), 404.
- [ ] Mobile menu, language menu (activate a second language), announcement banner.
- [ ] A dark palette stays readable (`Night` or your own).
- [ ] No external resource, no `style="…"` attribute, no inline script without nonce
      (open the browser console: no CSP error).
- [ ] Texts in English with `__()`, and `lang/*.json` for the languages you support.
- [ ] `assets/` built and committed; `screenshot.png` present.
