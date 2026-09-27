# MyCMS

*[Version française](README.fr.md)*

**A very simple, secure and multilingual CMS for everyone** — freelancers, small
businesses, associations, artists, local shops… Built with Laravel, installed in one
command, managed from a friendly administration that anyone can use.

- **Pages made of sections** — hero header, cards, text + picture, steps, prices, FAQ,
  gallery, video, map, contact form, free text… 20 ready-made section types, added,
  moved, hidden or duplicated in one click, with a live preview.
- **Site information in one place** — name, address, phone, opening hours, main button,
  social networks, legal information… Reused everywhere with tags like `{phone}`.
- **Themes** — two bundled themes (*Default* and *Minimal*), colour palettes with a
  readability check, theme options. Install new themes **from a public Git repository or
  a ZIP file**.
- **Languages** — administration in English or French (other languages installable
  **from a Git repository or a ZIP file**), and sites published in several languages
  (`/`, `/fr/…`) with translated pages, language menu and `hreflang`.
- **Secure by default** — mandatory two-factor authentication, progressive lockout,
  strict Content-Security-Policy, sanitised content, re-encoded images, encrypted
  contact messages with automatic GDPR purge, activity log.
- **Lightweight** — no JavaScript framework on the public site, fonts and icons hosted
  locally, no Node.js needed on the server, SQLite by default.

---

## Requirements

| | |
|---|---|
| **PHP** | 8.3 or newer, with the extensions `ctype curl dom fileinfo filter mbstring openssl pdo tokenizer xml` and `pdo_sqlite` (or `pdo_mysql` / `pdo_pgsql`). Recommended: `gd` (image optimisation), `zip`, `intl`. |
| **Database** | SQLite (nothing to configure), MySQL 8+, MariaDB 10.6+ or PostgreSQL 13+. |
| **Tools** | `git` or `curl`. Composer is downloaded automatically if missing. |
| **Web server** | Any server able to run PHP: Apache, nginx, Caddy, shared hosting with SSH access… |

---

## Installation

### 1. One command (Linux, macOS, WSL, Git Bash)

```bash
curl -fsSL https://raw.githubusercontent.com/mycms-project/mycms/main/install.sh | bash
```

The installer asks a few questions (language, folder, address, name of the site,
database, administrator account), checks the requirements, downloads MyCMS, installs
the dependencies, creates the database with starter pages and your account.
Then point your web server to the `public` folder — or try it right away with
`php artisan serve`.

**Automated installation** (CI, provisioning scripts…): every answer can be given as an
environment variable.

```bash
curl -fsSL https://raw.githubusercontent.com/mycms-project/mycms/main/install.sh | \
  MYCMS_NONINTERACTIVE=1 \
  MYCMS_DIR=/var/www/mysite \
  MYCMS_URL=https://example.com \
  MYCMS_LOCALE=en \
  MYCMS_SITE_NAME="My Website" \
  MYCMS_ADMIN_EMAIL=me@example.com \
  MYCMS_ADMIN_PASSWORD='A-Long-Password-2026' \
  bash
```

Other variables: `MYCMS_DB` (`sqlite`, `mysql`, `mariadb`, `pgsql`), `MYCMS_DB_HOST`,
`MYCMS_DB_PORT`, `MYCMS_DB_NAME`, `MYCMS_DB_USER`, `MYCMS_DB_PASSWORD`,
`MYCMS_ADMIN_NAME`, `MYCMS_BRANCH` (a tag, e.g. `v1.0.0`), `MYCMS_REPO`, `PHP_BIN`.

### 2. Manual installation

```bash
git clone https://github.com/mycms-project/mycms.git mysite
cd mysite
composer install --no-dev --optimize-autoloader
cp .env.example .env          # then edit APP_URL, APP_LOCALE, DB_* and MAIL_*
php artisan key:generate
touch database/database.sqlite   # with SQLite
php artisan mycms:install --locale=en --site-name="My Website" --admin-email=me@example.com
```

The last command creates the tables, the starter pages and the administrator (the
password is asked). It can be run again safely: an installed site is never replaced.

### 3. Docker

```bash
cp .env.docker.example .env.docker    # fill in APP_KEY, APP_URL, passwords…
docker compose --env-file .env.docker up -d
```

The site listens on port `8080` of the `app` service; put a reverse proxy with HTTPS in
front of it (Traefik, Caddy, nginx, Coolify…). Pictures, installed themes and languages
are stored in the `storage` volume, the database in the `db` volume: back up both.

---

## After the installation

1. **Web server** — the document root must be the `public` folder.

   Apache: `public/.htaccess` is provided (enable `mod_rewrite`).

   nginx:
   ```nginx
   server {
       server_name example.com;
       root /var/www/mysite/public;
       index index.php;
       location / { try_files $uri $uri/ /index.php?$query_string; }
       location ~ \.php$ {
           include fastcgi_params;
           fastcgi_pass unix:/run/php/php8.3-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
       }
       location ~ /\.(?!well-known) { deny all; }
   }
   ```
   The `storage`, `bootstrap/cache` and `database` folders must be writable by PHP.

2. **HTTPS** — use a certificate (Let's Encrypt…) and an `https://` `APP_URL`.

3. **Scheduled tasks** — add this line to the crontab (GDPR purge of old messages…):
   ```
   * * * * * cd /var/www/mysite && php artisan schedule:run >> /dev/null 2>&1
   ```

4. **E-mails** — fill in the `MAIL_*` lines of `.env` (SMTP of your host, Brevo,
   Mailjet…), then `php artisan optimize`. They are used for security alerts, forgotten
   passwords and contact form notifications.

5. **First login** — open `https://example.com/admin`, log in and set up two-factor
   authentication with an app (Google Authenticator, Microsoft Authenticator, 2FAS…).
   Then fill in **Site information** and, when the site is ready, allow search engines
   in **Site information → Search engines**.

### Useful commands

```bash
php artisan mycms:admin me@example.com              # create an administrator
php artisan mycms:admin --list                      # list the administrators
php artisan mycms:admin me@example.com --password   # set a new password
php artisan mycms:admin me@example.com --reset-2fa  # lost phone, no recovery code
php artisan mycms:admin me@example.com --revoke     # remove the rights
php artisan mycms:unlock me@example.com             # unblock after failed attempts
php artisan mycms:theme list|install|activate|update|delete
php artisan mycms:language list|install|enable|disable|update|delete
php artisan mycms:translations fr                   # check a language pack
```

### Configuration (`.env`)

| Variable | Default | Description |
|---|---|---|
| `ADMIN_PATH` | `admin` | Address of the administration (`/admin`). |
| `ADMIN_DOMAIN` | *(empty)* | Serve the administration on a dedicated sub-domain (`admin.example.com`) for stronger isolation. |
| `ADMIN_REQUIRE_2FA` | `true` | Make two-factor authentication mandatory. |
| `ADMIN_IDLE_MINUTES` | `60` | Automatic logout after inactivity. |
| `MYCMS_ALLOW_PACKAGE_INSTALL` | `true` | Allow installing themes and languages from the administration. |
| `MYCMS_MESSAGES_RETENTION` | `12` | Months before contact messages are deleted. |

### Updating MyCMS

```bash
cd /var/www/mysite
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan mycms:install        # applies the new migrations, keeps your content
php artisan optimize
php artisan up
```

---

## Themes and languages

- Create a theme: **[docs/THEMES.md](docs/THEMES.md)** ([français](docs/fr/THEMES.md))
- Translate MyCMS or publish a site in several languages: **[docs/LANGUAGES.md](docs/LANGUAGES.md)** ([français](docs/fr/LANGUAGES.md))

Both can be installed from the administration (**Themes** / **Languages** → *Install*)
with the address of a public Git repository (`https://github.com/someone/repo`,
`…#v1.2` for a version) or a ZIP file.

---

## Development

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan mycms:install --locale=en --admin-email=dev@example.test
npm run build          # administration + bundled themes (committed: servers need no Node.js)
php artisan serve
php artisan test       # test suite
```

- `npm run dev` — administration with hot reload; `npm run dev:theme` — rebuilds the default theme on change.
- Code style: `vendor/bin/pint`.

### Structure

```
app/Cms/                     Core: sections, site information, palette, sanitising, media, 2FA
app/Cms/Themes/              Themes (discovery, activation, installation)
app/Cms/Languages/           Language packs and translation loader
app/Cms/Packages/            Secure download / extraction of packages (Git, ZIP)
app/Http/Controllers/Site    Public site (pages, contact, media, theme files, sitemap)
app/Http/Controllers/Admin   Administration
resources/themes/            Bundled themes (default, minimal)
resources/languages/         Bundled language packs (fr)
resources/views/admin/       Administration screens
resources/icons/             Material Symbols icons (SVG)
database/seeders/            Starter content
install.sh                   One-command installer
```

---

## Security

Found a vulnerability? Please do not open a public issue: write to the maintainers
(see the repository's *Security* tab). Main protections: mandatory 2FA with anti-replay
codes and recovery codes, progressive lockout (also on unknown e-mails), passwords
checked against known leaks, idle logout, strict CSP with nonces, HTML sanitised with
Symfony HtmlSanitizer, `javascript:` links refused, images re-encoded (EXIF/GPS
removed), contact messages encrypted at rest, packages downloaded over HTTPS only from
public hosts, archives checked (no path traversal, no symbolic links, size limits),
language packs limited to JSON.

## License

MyCMS is free software released under the [MIT license](LICENSE).
Icons: [Material Symbols](https://fonts.google.com/icons) (Apache 2.0).
Fonts: Literata, Nunito Sans (SIL Open Font License).
