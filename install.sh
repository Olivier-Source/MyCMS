#!/usr/bin/env bash
#
# MyCMS installer
# ---------------
#   curl -fsSL https://raw.githubusercontent.com/mycms-project/mycms/main/install.sh | bash
#
# Interactive by default. For automated installs, set MYCMS_NONINTERACTIVE=1
# and the variables below (all optional except MYCMS_ADMIN_EMAIL / _PASSWORD):
#
#   MYCMS_REPO            Git repository to install from
#   MYCMS_BRANCH          Branch or tag (default: main)
#   MYCMS_DIR             Installation folder (default: ./mycms)
#   MYCMS_URL             Public address of the site (default: http://localhost:8000)
#   MYCMS_LOCALE          Language of the site: en, fr (default: en)
#   MYCMS_SITE_NAME       Name of the site
#   MYCMS_DB              sqlite (default), mysql, mariadb or pgsql
#   MYCMS_DB_HOST / MYCMS_DB_PORT / MYCMS_DB_NAME / MYCMS_DB_USER / MYCMS_DB_PASSWORD
#   MYCMS_ADMIN_EMAIL / MYCMS_ADMIN_NAME / MYCMS_ADMIN_PASSWORD
#
set -euo pipefail

MYCMS_REPO="${MYCMS_REPO:-https://github.com/mycms-project/mycms}"
MYCMS_BRANCH="${MYCMS_BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
NONINTERACTIVE="${MYCMS_NONINTERACTIVE:-0}"
LANG_UI="${MYCMS_LOCALE:-}"

# ---------------------------------------------------------------------------
# Output helpers
# ---------------------------------------------------------------------------
if [ -t 1 ]; then
    BOLD=$'\033[1m'; GREEN=$'\033[32m'; YELLOW=$'\033[33m'; RED=$'\033[31m'; BLUE=$'\033[34m'; RESET=$'\033[0m'
else
    BOLD=''; GREEN=''; YELLOW=''; RED=''; BLUE=''; RESET=''
fi

# t "English text" "Texte français"
t() { if [ "$LANG_UI" = "fr" ]; then printf '%s' "$2"; else printf '%s' "$1"; fi; }
step() { printf '\n%s==> %s%s\n' "$BLUE$BOLD" "$1" "$RESET"; }
ok()   { printf '%s  ✔ %s%s\n' "$GREEN" "$1" "$RESET"; }
warn() { printf '%s  ! %s%s\n' "$YELLOW" "$1" "$RESET"; }
fail() { printf '\n%s✘ %s%s\n' "$RED$BOLD" "$1" "$RESET" >&2; exit 1; }

# The script is usually piped into bash: questions are read from the terminal
if [ "$NONINTERACTIVE" != "1" ] && ! { exec 3</dev/tty; } 2>/dev/null; then
    NONINTERACTIVE=1
fi

# ask VAR "Question" "default"
ask() {
    local var="$1" question="$2" default="${3:-}" answer=""
    if [ -n "${!var:-}" ]; then return; fi
    if [ "$NONINTERACTIVE" = "1" ]; then printf -v "$var" '%s' "$default"; return; fi
    if [ -n "$default" ]; then printf '%s %s[%s]%s ' "$question" "$BOLD" "$default" "$RESET"; else printf '%s ' "$question"; fi
    IFS= read -r answer <&3 || true
    printf -v "$var" '%s' "${answer:-$default}"
}

# ask_secret VAR "Question"
ask_secret() {
    local var="$1" question="$2" first="" second=""
    if [ -n "${!var:-}" ]; then return; fi
    [ "$NONINTERACTIVE" = "1" ] && fail "$var $(t 'is required in non-interactive mode.' 'est obligatoire en mode non interactif.')"
    while true; do
        printf '%s ' "$question"; IFS= read -rs first <&3; printf '\n'
        printf '%s ' "$(t 'Confirm:' 'Confirmez :')"; IFS= read -rs second <&3; printf '\n'
        if [ "$first" != "$second" ]; then warn "$(t 'The two entries are different.' 'Les deux saisies sont différentes.')"; continue; fi
        if [ ${#first} -lt 12 ] || ! [[ "$first" =~ [a-z] ]] || ! [[ "$first" =~ [A-Z] ]] || ! [[ "$first" =~ [0-9] ]]; then
            warn "$(t '12 characters minimum, with upper and lower case letters and digits.' '12 caractères minimum, avec majuscules, minuscules et chiffres.')"
            continue
        fi
        printf -v "$var" '%s' "$first"; return
    done
}

# ---------------------------------------------------------------------------
# 0. Language of the installer
# ---------------------------------------------------------------------------
printf '\n%s  __  __        ____ __  __ ____  %s\n' "$BOLD" "$RESET"
printf '%s |  \\/  |_   _ / ___|  \\/  / ___| %s\n' "$BOLD" "$RESET"
printf '%s | |\\/| | | | | |   | |\\/| \\___ \\ %s\n' "$BOLD" "$RESET"
printf '%s | |  | | |_| | |___| |  | |___) |%s\n' "$BOLD" "$RESET"
printf '%s |_|  |_|\\__, |\\____|_|  |_|____/ %s\n' "$BOLD" "$RESET"
printf '%s         |___/                    %s\n\n' "$BOLD" "$RESET"

if [ -z "$LANG_UI" ]; then
    ask LANG_UI "Language / Langue (en, fr)" "en"
fi
case "$LANG_UI" in fr|en) ;; *) LANG_UI="en" ;; esac
MYCMS_LOCALE="$LANG_UI"

# ---------------------------------------------------------------------------
# 1. Requirements
# ---------------------------------------------------------------------------
step "$(t 'Checking the requirements' 'Vérification des prérequis')"

command -v "$PHP_BIN" >/dev/null 2>&1 || fail "$(t 'PHP 8.3 or newer is required: https://www.php.net/downloads' 'PHP 8.3 ou plus récent est nécessaire : https://www.php.net/downloads')"
"$PHP_BIN" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' \
    || fail "$(t "PHP 8.3 or newer is required (found $("$PHP_BIN" -r 'echo PHP_VERSION;'))." "PHP 8.3 ou plus récent est nécessaire (trouvé : $("$PHP_BIN" -r 'echo PHP_VERSION;')).")"
ok "PHP $("$PHP_BIN" -r 'echo PHP_VERSION;')"

missing=""
for ext in ctype curl dom fileinfo filter mbstring openssl pdo tokenizer xml; do
    "$PHP_BIN" -r "exit(extension_loaded('$ext') ? 0 : 1);" || missing="$missing $ext"
done
[ -z "$missing" ] || fail "$(t 'Missing PHP extensions:' 'Extensions PHP manquantes :')$missing"
ok "$(t 'Required PHP extensions' 'Extensions PHP nécessaires')"

for ext in gd zip intl; do
    "$PHP_BIN" -r "exit(extension_loaded('$ext') ? 0 : 1);" \
        || warn "$(t "Recommended PHP extension missing: $ext" "Extension PHP recommandée absente : $ext")"
done

command -v git >/dev/null 2>&1 || command -v curl >/dev/null 2>&1 \
    || fail "$(t 'git or curl is required to download MyCMS.' 'git ou curl est nécessaire pour télécharger MyCMS.')"

# ---------------------------------------------------------------------------
# 2. Questions
# ---------------------------------------------------------------------------
step "$(t 'Your site' 'Votre site')"
ask MYCMS_DIR "$(t 'Installation folder' 'Dossier d installation')" "./mycms"
ask MYCMS_URL "$(t 'Address of the site' 'Adresse du site')" "http://localhost:8000"
ask MYCMS_SITE_NAME "$(t 'Name of the site' 'Nom du site')" "$(t 'My Website' 'Mon site')"

step "$(t 'Database' 'Base de données')"
printf '%s\n' "$(t '  sqlite  = nothing to configure (recommended for most sites)' '  sqlite  = rien à configurer (recommandé pour la plupart des sites)')"
printf '%s\n' "$(t '  mysql / mariadb / pgsql = an existing database on your server' '  mysql / mariadb / pgsql = une base existante sur votre serveur')"
ask MYCMS_DB "$(t 'Database type' 'Type de base de données')" "sqlite"
case "$MYCMS_DB" in
    sqlite) "$PHP_BIN" -r "exit(extension_loaded('pdo_sqlite') ? 0 : 1);" || fail "$(t 'The PHP extension pdo_sqlite is missing.' 'L extension PHP pdo_sqlite est absente.')" ;;
    mysql|mariadb)
        "$PHP_BIN" -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);" || fail "$(t 'The PHP extension pdo_mysql is missing.' 'L extension PHP pdo_mysql est absente.')"
        ask MYCMS_DB_HOST "$(t 'Host' 'Hôte')" "127.0.0.1"
        ask MYCMS_DB_PORT "Port" "3306" ;;
    pgsql)
        "$PHP_BIN" -r "exit(extension_loaded('pdo_pgsql') ? 0 : 1);" || fail "$(t 'The PHP extension pdo_pgsql is missing.' 'L extension PHP pdo_pgsql est absente.')"
        ask MYCMS_DB_HOST "$(t 'Host' 'Hôte')" "127.0.0.1"
        ask MYCMS_DB_PORT "Port" "5432" ;;
    *) fail "$(t 'Unknown database type:' 'Type de base inconnu :') $MYCMS_DB" ;;
esac
if [ "$MYCMS_DB" != "sqlite" ]; then
    ask MYCMS_DB_NAME "$(t 'Database name' 'Nom de la base')" "mycms"
    ask MYCMS_DB_USER "$(t 'User' 'Utilisateur')" "mycms"
    if [ -z "${MYCMS_DB_PASSWORD+x}" ] && [ "$NONINTERACTIVE" != "1" ]; then
        printf '%s ' "$(t 'Password:' 'Mot de passe :')"; IFS= read -rs MYCMS_DB_PASSWORD <&3; printf '\n'
    fi
    MYCMS_DB_PASSWORD="${MYCMS_DB_PASSWORD:-}"
fi

step "$(t 'Administrator account' 'Compte administrateur')"
ask MYCMS_ADMIN_EMAIL "$(t 'Your e-mail address' 'Votre adresse e-mail')" ""
[[ "$MYCMS_ADMIN_EMAIL" == *@*.* ]] || fail "$(t 'A valid e-mail address is required.' 'Une adresse e-mail valide est obligatoire.')"
ask MYCMS_ADMIN_NAME "$(t 'Your name' 'Votre nom')" "${MYCMS_ADMIN_EMAIL%%@*}"
ask_secret MYCMS_ADMIN_PASSWORD "$(t 'Password (12 characters min., upper and lower case letters, digits):' 'Mot de passe (12 caractères min., majuscules, minuscules, chiffres) :')"

# ---------------------------------------------------------------------------
# 3. Download
# ---------------------------------------------------------------------------
step "$(t 'Downloading MyCMS' 'Téléchargement de MyCMS')"
if [ -e "$MYCMS_DIR" ] && [ -n "$(ls -A "$MYCMS_DIR" 2>/dev/null)" ]; then
    fail "$(t "The folder $MYCMS_DIR already exists and is not empty." "Le dossier $MYCMS_DIR existe déjà et n'est pas vide.")"
fi
if command -v git >/dev/null 2>&1; then
    git clone --quiet --depth 1 --branch "$MYCMS_BRANCH" "$MYCMS_REPO" "$MYCMS_DIR" \
        || fail "$(t 'The repository could not be downloaded:' 'Le dépôt n a pas pu être téléchargé :') $MYCMS_REPO"
    # .git is kept: updating MyCMS is then a simple "git pull" (see README)
else
    mkdir -p "$MYCMS_DIR"
    curl -fsSL "$MYCMS_REPO/archive/$MYCMS_BRANCH.tar.gz" | tar -xz --strip-components=1 -C "$MYCMS_DIR" \
        || fail "$(t 'The archive could not be downloaded:' 'L archive n a pas pu être téléchargée :') $MYCMS_REPO"
fi
cd "$MYCMS_DIR"
ok "$(pwd)"

# ---------------------------------------------------------------------------
# 4. PHP dependencies (Composer is downloaded if needed)
# ---------------------------------------------------------------------------
step "$(t 'Installing the PHP dependencies (1-2 minutes)' 'Installation des dépendances PHP (1-2 minutes)')"
if command -v composer >/dev/null 2>&1; then
    COMPOSER=(composer)
else
    warn "$(t 'Composer not found: downloading it into the site folder.' 'Composer introuvable : téléchargement dans le dossier du site.')"
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o composer-setup.php
    ACTUAL="$("$PHP_BIN" -r "echo hash_file('sha384', 'composer-setup.php');")"
    [ "$EXPECTED" = "$ACTUAL" ] || { rm -f composer-setup.php; fail "$(t 'Invalid Composer installer signature.' 'Signature de l installateur Composer invalide.')"; }
    "$PHP_BIN" composer-setup.php --quiet && rm -f composer-setup.php
    COMPOSER=("$PHP_BIN" composer.phar)
fi
"${COMPOSER[@]}" install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet \
    || fail "$(t 'composer install failed.' 'composer install a échoué.')"
ok "$(t 'Dependencies installed' 'Dépendances installées')"

# ---------------------------------------------------------------------------
# 5. Configuration (.env)
# ---------------------------------------------------------------------------
step "$(t 'Configuration' 'Configuration')"
cp .env.example .env
export MYCMS_SITE_NAME MYCMS_URL MYCMS_LOCALE MYCMS_DB_HOST MYCMS_DB_PORT MYCMS_DB_NAME MYCMS_DB_USER MYCMS_DB_PASSWORD
# Values are written by PHP (no escaping problem with special characters)
MYCMS_ENV_DB="$MYCMS_DB" "$PHP_BIN" -r '
    $env = file_get_contents(".env");
    $set = function (string $key, ?string $value) use (&$env) {
        if ($value === null) { return; }
        $line = $key."=".(preg_match("/^[A-Za-z0-9_.:\/\-]*$/", $value) ? $value : "\"".addcslashes($value, "\"\\\$")."\"");
        $env = preg_match("/^#?\s*".$key."=.*$/m", $env)
            ? preg_replace("/^#?\s*".$key."=.*$/m", str_replace("$", "\\$", $line), $env, 1)
            : $env."\n".$line;
    };
    $db = getenv("MYCMS_ENV_DB");
    $set("APP_NAME", getenv("MYCMS_SITE_NAME"));
    $set("APP_URL", rtrim(getenv("MYCMS_URL"), "/"));
    $set("APP_LOCALE", getenv("MYCMS_LOCALE"));
    $set("APP_FALLBACK_LOCALE", "en");
    $set("DB_CONNECTION", $db);
    if ($db !== "sqlite") {
        foreach (["DB_HOST" => "MYCMS_DB_HOST", "DB_PORT" => "MYCMS_DB_PORT", "DB_DATABASE" => "MYCMS_DB_NAME", "DB_USERNAME" => "MYCMS_DB_USER", "DB_PASSWORD" => "MYCMS_DB_PASSWORD"] as $k => $v) {
            $set($k, (string) getenv($v));
        }
    }
    if (str_starts_with(getenv("MYCMS_URL"), "http://localhost") || str_starts_with(getenv("MYCMS_URL"), "http://127.0.0.1")) {
        $set("SESSION_SECURE_COOKIE", "false");
    }
    file_put_contents(".env", $env);
' || fail "$(t 'The .env file could not be written.' 'Le fichier .env n a pas pu être écrit.')"

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
[ "$MYCMS_DB" = "sqlite" ] && touch database/database.sqlite
"$PHP_BIN" artisan key:generate --force --no-interaction >/dev/null
ok ".env"

# ---------------------------------------------------------------------------
# 6. Database, starter content, administrator
# ---------------------------------------------------------------------------
step "$(t 'Installing the site' 'Installation du site')"
MYCMS_ADMIN_PASSWORD="$MYCMS_ADMIN_PASSWORD" "$PHP_BIN" artisan mycms:install \
    --locale="$MYCMS_LOCALE" \
    --site-name="$MYCMS_SITE_NAME" \
    --email="$MYCMS_ADMIN_EMAIL" \
    --admin-email="$MYCMS_ADMIN_EMAIL" \
    --admin-name="$MYCMS_ADMIN_NAME" \
    --no-interaction \
    || fail "$(t 'The installation failed (check the database settings in .env).' 'L installation a échoué (vérifiez les réglages de base de données dans .env).')"

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/themes storage/app/languages bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache database 2>/dev/null || true
"$PHP_BIN" artisan optimize --no-interaction >/dev/null 2>&1 || true
ok "$(t 'Site installed' 'Site installé')"

# ---------------------------------------------------------------------------
# 7. Summary
# ---------------------------------------------------------------------------
ADMIN_URL="${MYCMS_URL%/}/admin"
printf '\n%s%s%s\n\n' "$GREEN$BOLD" "$(t 'MyCMS is installed!' 'MyCMS est installé !')" "$RESET"
printf '  %s %s\n' "$(t 'Site:          ' 'Site :          ')" "$MYCMS_URL"
printf '  %s %s\n' "$(t 'Administration:' 'Administration :')" "$ADMIN_URL"
printf '  %s %s\n\n' "$(t 'Folder:        ' 'Dossier :       ')" "$(pwd)"
printf '%s\n' "$(t 'Next steps:' 'Étapes suivantes :')"
printf '  1. %s\n' "$(t 'Web server: point the document root to the "public" folder.' 'Serveur web : faites pointer la racine du site vers le dossier « public ».')"
printf '     %s php artisan serve\n' "$(t 'To try it right now:' 'Pour essayer tout de suite :')"
printf '  2. %s\n' "$(t 'Scheduled tasks (cron), once a minute:' 'Tâches planifiées (cron), chaque minute :')"
printf '     * * * * * cd %s && php artisan schedule:run >> /dev/null 2>&1\n' "$(pwd)"
printf '  3. %s\n' "$(t 'E-mails: fill in the MAIL_* lines of the .env file, then run "php artisan optimize".' 'E-mails : complétez les lignes MAIL_* du fichier .env, puis lancez « php artisan optimize ».')"
printf '  4. %s\n\n' "$(t 'At the first login, set up two-factor authentication with an app on your phone.' 'À la première connexion, configurez la double authentification avec une application sur votre téléphone.')"

if [ "$NONINTERACTIVE" != "1" ]; then
    START=""
    ask START "$(t 'Start a test server now? (y/N)' 'Démarrer un serveur de test maintenant ? (o/N)')" "n"
    case "$START" in
        y|Y|o|O|yes|oui)
            printf '%s\n' "$(t 'Press Ctrl+C to stop it.' 'Ctrl+C pour l arrêter.')"
            exec "$PHP_BIN" artisan serve ;;
    esac
fi
