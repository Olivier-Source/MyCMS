# Languages in MyCMS

*[Version française](fr/LANGUAGES.md)*

MyCMS handles two different things:

1. **The language of the interface** — the texts of MyCMS itself: administration,
   buttons and messages of the public site ("Send my message", "Home"…), e-mails,
   error messages. They come from **language packs**.
2. **The language of your content** — your pages and your site information. You write
   them yourself; a site can be published in several languages (see §6).

**English** is the source language of MyCMS (always available). **French** is bundled.
Any other language can be installed from a Git repository or a ZIP file.

---

## 1. Prerequisites

To *create* a language pack you need:

- a text editor that saves **UTF-8** (VS Code, Notepad++, Sublime Text…);
- to know the JSON format (`"key": "value"`, commas, quotes escaped as `\"`);
- *recommended:* a local MyCMS to generate the template and check your work
  (`php artisan mycms:translations`, see §3). No programming is required.

A language pack contains **only JSON files**: it cannot run any code, so installing
one is always safe.

---

## 2. Structure of a language pack

```
mycms-lang-de/
├── language.json      ← required: the manifest
├── messages.json      ← interface texts ("English text" → translation)
├── validation.json    ← form error messages (optional but recommended)
├── passwords.json     ← password reset messages (optional)
├── pagination.json    ← "Previous" / "Next" (optional)
└── auth.json          ← login messages (optional)
```

Copy `resources/languages/fr` as a starting point: it is a complete pack.

### `language.json`

```json
{
    "code": "de",
    "name": "German",
    "native": "Deutsch",
    "version": "1.0.0",
    "author": "Your name",
    "direction": "ltr",
    "og_locale": "de_DE"
}
```

| Key | Required | Description |
|---|---|---|
| `code` | yes | Language code: `de`, `es`, `it`… or with a region `pt_BR`, `zh_TW` (letters + `_` + region). Used in the addresses of the site: `/de/…`, `/pt-br/…`. `en` and the bundled languages cannot be replaced. |
| `name` | yes | Name of the language in English. |
| `native` | no | Name in the language itself, shown in the menus ("Deutsch"). |
| `direction` | no | `ltr` (default) or `rtl` for Arabic, Hebrew, Persian… |
| `og_locale` | no | Value for social networks (`de_DE`). |
| `version`, `author` | no | Shown in the administration. |

### `messages.json`

Keys are the **exact English texts** of MyCMS, values are your translations:

```json
{
    "Save": "Speichern",
    "Hello :name": "Hallo :name",
    ":count page|:count pages": ":count Seite|:count Seiten",
    "Welcome to {site_name}": "Willkommen bei {site_name}",
    "“:text”": "„:text“"
}
```

Rules:

- **Keep the placeholders** starting with `:` (`:name`, `:count`, `:title`…) — you may move
  them in the sentence.
- **Keep the tags** between braces (`{site_name}`, `{email}`, `{phone}`…): they are
  replaced by the site information.
- **Plurals**: `singular|plural` separated by `|` (the same number of forms as in English
  is enough; advanced rules: [Laravel pluralization](https://laravel.com/docs/localization#pluralization)).
- A few keys are **addresses** of the starter pages: `about`, `services`, `contact`,
  `legal-notice`, `privacy-policy`. Translate them as lowercase words separated by
  dashes, without accents (`uber-uns`, `impressum`…).
- `“:text”` gives the quotation marks of your language.
- A missing key simply shows the English text: a partial pack is usable.

### Group files (`validation.json`…)

Same content as Laravel's PHP translation files, in JSON. Take them from
`resources/languages/fr/*.json`, or from the community project
[Laravel-Lang](https://github.com/Laravel-Lang/lang) (convert the PHP arrays to JSON).

---

## 3. Generating the template and checking your work

On a local MyCMS:

```bash
# Writes storage/app/translations/de/messages.json with every text to translate
# (empty values) and a language.json to complete
php artisan mycms:translations de --template

# After installing your pack: lists the missing and obsolete texts
php artisan mycms:translations de
```

`--template` keeps the translations already present in the installed pack: run it again
after each MyCMS update to get the new texts.

---

## 4. Themes and translations

Themes also contain texts. MyCMS merges, for each language:

1. the `lang/{code}.json` files of the themes,
2. then `messages.json` of the language pack (it wins over the themes),
3. then `lang/{code}.json` at the root of the site, if you created one (it wins over
   everything: handy to adjust a single text on your site without touching the pack).

A language pack may therefore also translate the texts of the bundled themes. See
[THEMES.md](THEMES.md#9-translations) to translate your own theme.

---

## 5. Publishing and installing

1. Put the files in a **public Git repository** (`language.json` at the root), e.g.
   `mycms-lang-de`. Tag your versions (`v1.0.0`).
2. Install it from **Languages → Install a language**: address of the repository
   (`https://github.com/you/mycms-lang-de`, or `…#v1.0.0` for a version), a link to a
   `.zip`, or an uploaded `.zip`.
3. Or from the command line:
   ```bash
   php artisan mycms:language install https://github.com/you/mycms-lang-de
   php artisan mycms:language update de
   php artisan mycms:language list
   ```

Installed packs are stored in `storage/app/languages/{code}`.

To offer your translation to everybody, open a pull request adding it to
`resources/languages/` of the MyCMS repository.

---

## 6. Publishing a site in several languages

1. **Languages**: tick the languages of the site, choose the default language, save.
   - The default language is served at the root: `/`, `/about`.
   - The others under a prefix: `/de`, `/de/uber-uns`.
2. **Pages**: open a page and click **Translate into…**. A linked copy is created in the
   other language: translate its title, its address and each section, then publish it.
3. **Site information**: choose the language at the top of the screen to translate the
   name, tagline, main button, footer, announcement… Empty fields use the default
   language.

Visitors get a language menu; search engines get `hreflang` links and a sitemap with
all the languages.

The **administration language** is chosen by each administrator in **My account** (the
default is set in **Languages**).
