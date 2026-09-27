# MyCMS

*[English version](README.md)*

**Un CMS très simple, sécurisé et multilingue, pour tout le monde** — indépendants,
petites entreprises, associations, artistes, commerces… Construit avec Laravel, installé
en une commande, géré depuis une administration claire que chacun peut utiliser.

- **Des pages composées de sections** — grand en-tête, cartes, texte + photo, étapes,
  tarifs, FAQ, galerie, vidéo, plan, formulaire de contact, texte libre… 20 types de
  sections prêts à l'emploi, ajoutés, déplacés, masqués ou dupliqués en un clic, avec un
  aperçu en direct.
- **Les informations du site au même endroit** — nom, adresse, téléphone, horaires,
  bouton principal, réseaux sociaux, mentions légales… Réutilisées partout grâce à des
  balises comme `{phone}`.
- **Thèmes** — deux thèmes fournis (*Par défaut* et *Minimal*), palettes de couleurs avec
  contrôle de lisibilité, options de thème. Installation de nouveaux thèmes **depuis un
  dépôt Git public ou un fichier ZIP**.
- **Langues** — administration en français ou en anglais (autres langues installables
  **depuis un dépôt Git ou un fichier ZIP**), et sites publiés en plusieurs langues
  (`/`, `/en/…`) avec pages traduites, menu de langue et `hreflang`.
- **Sécurisé par défaut** — double authentification obligatoire, verrouillage progressif,
  Content-Security-Policy stricte, contenu nettoyé, images ré-encodées, messages de
  contact chiffrés et purgés automatiquement (RGPD), journal d'activité.
- **Léger** — aucun framework JavaScript sur le site public, polices et icônes hébergées
  localement, pas besoin de Node.js sur le serveur, SQLite par défaut.

---

## Prérequis

| | |
|---|---|
| **PHP** | 8.3 ou plus récent, avec les extensions `ctype curl dom fileinfo filter mbstring openssl pdo tokenizer xml` et `pdo_sqlite` (ou `pdo_mysql` / `pdo_pgsql`). Conseillées : `gd` (optimisation des images), `zip`, `intl`. |
| **Base de données** | SQLite (rien à configurer), MySQL 8+, MariaDB 10.6+ ou PostgreSQL 13+. |
| **Outils** | `git` ou `curl`. Composer est téléchargé automatiquement s'il manque. |
| **Serveur web** | Tout serveur capable d'exécuter PHP : Apache, nginx, Caddy, hébergement mutualisé avec accès SSH… |

---

## Installation

### 1. Une seule commande (Linux, macOS, WSL, Git Bash)

```bash
curl -fsSL https://raw.githubusercontent.com/mycms-project/mycms/main/install.sh | bash
```

L'installateur pose quelques questions (langue, dossier, adresse, nom du site, base de
données, compte administrateur), vérifie les prérequis, télécharge MyCMS, installe les
dépendances, crée la base avec des pages de départ et votre compte. Faites ensuite
pointer votre serveur web vers le dossier `public` — ou essayez tout de suite avec
`php artisan serve`.

**Installation automatisée** (CI, scripts de déploiement…) : chaque réponse peut être
donnée par une variable d'environnement.

```bash
curl -fsSL https://raw.githubusercontent.com/mycms-project/mycms/main/install.sh | \
  MYCMS_NONINTERACTIVE=1 \
  MYCMS_DIR=/var/www/monsite \
  MYCMS_URL=https://exemple.fr \
  MYCMS_LOCALE=fr \
  MYCMS_SITE_NAME="Mon site" \
  MYCMS_ADMIN_EMAIL=moi@exemple.fr \
  MYCMS_ADMIN_PASSWORD='Un-Long-Mot-De-Passe-2026' \
  bash
```

Autres variables : `MYCMS_DB` (`sqlite`, `mysql`, `mariadb`, `pgsql`), `MYCMS_DB_HOST`,
`MYCMS_DB_PORT`, `MYCMS_DB_NAME`, `MYCMS_DB_USER`, `MYCMS_DB_PASSWORD`,
`MYCMS_ADMIN_NAME`, `MYCMS_BRANCH` (une version, ex. `v1.0.0`), `MYCMS_REPO`, `PHP_BIN`.

### 2. Installation manuelle

```bash
git clone https://github.com/mycms-project/mycms.git monsite
cd monsite
composer install --no-dev --optimize-autoloader
cp .env.example .env          # puis modifiez APP_URL, APP_LOCALE=fr, DB_* et MAIL_*
php artisan key:generate
touch database/database.sqlite   # avec SQLite
php artisan mycms:install --locale=fr --site-name="Mon site" --admin-email=moi@exemple.fr
```

La dernière commande crée les tables, les pages de départ et l'administrateur (le mot de
passe est demandé). Elle peut être relancée sans risque : un site installé n'est jamais
remplacé.

### 3. Docker

```bash
cp .env.docker.example .env.docker    # renseignez APP_KEY, APP_URL, mots de passe…
docker compose --env-file .env.docker up -d
```

Le site écoute sur le port `8080` du service `app` ; placez devant un proxy inverse avec
HTTPS (Traefik, Caddy, nginx, Coolify…). Les images, thèmes et langues installés sont
dans le volume `storage`, la base dans le volume `db` : sauvegardez les deux.

---

## Après l'installation

1. **Serveur web** — la racine du site doit être le dossier `public`.

   Apache : `public/.htaccess` est fourni (activez `mod_rewrite`).

   nginx :
   ```nginx
   server {
       server_name exemple.fr;
       root /var/www/monsite/public;
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
   Les dossiers `storage`, `bootstrap/cache` et `database` doivent être accessibles en
   écriture par PHP.

2. **HTTPS** — utilisez un certificat (Let's Encrypt…) et une adresse `APP_URL` en `https://`.

3. **Tâches planifiées** — ajoutez cette ligne à la crontab (purge RGPD des anciens messages…) :
   ```
   * * * * * cd /var/www/monsite && php artisan schedule:run >> /dev/null 2>&1
   ```

4. **E-mails** — complétez les lignes `MAIL_*` du fichier `.env` (SMTP de votre hébergeur,
   Brevo, Mailjet…), puis `php artisan optimize`. Ils servent aux alertes de sécurité, au
   mot de passe oublié et aux notifications du formulaire de contact.

5. **Première connexion** — ouvrez `https://exemple.fr/admin`, connectez-vous et
   configurez la double authentification avec une application (Google Authenticator,
   Microsoft Authenticator, 2FAS…). Remplissez ensuite les **Informations du site** et,
   quand le site est prêt, autorisez les moteurs de recherche dans **Informations du
   site → Référencement**.

### Commandes utiles

```bash
php artisan mycms:admin moi@exemple.fr              # créer un administrateur
php artisan mycms:admin --list                      # lister les administrateurs
php artisan mycms:admin moi@exemple.fr --password   # définir un nouveau mot de passe
php artisan mycms:admin moi@exemple.fr --reset-2fa  # téléphone perdu, plus de code de secours
php artisan mycms:admin moi@exemple.fr --revoke     # retirer les droits
php artisan mycms:unlock moi@exemple.fr             # débloquer après des tentatives échouées
php artisan mycms:theme list|install|activate|update|delete
php artisan mycms:language list|install|enable|disable|update|delete
php artisan mycms:translations fr                   # vérifier un pack de langue
```

### Configuration (`.env`)

| Variable | Défaut | Description |
|---|---|---|
| `ADMIN_PATH` | `admin` | Adresse de l'administration (`/admin`). |
| `ADMIN_DOMAIN` | *(vide)* | Servir l'administration sur un sous-domaine dédié (`admin.exemple.fr`) pour une isolation renforcée. |
| `ADMIN_REQUIRE_2FA` | `true` | Rendre la double authentification obligatoire. |
| `ADMIN_IDLE_MINUTES` | `60` | Déconnexion automatique après inactivité. |
| `MYCMS_ALLOW_PACKAGE_INSTALL` | `true` | Autoriser l'installation de thèmes et de langues depuis l'administration. |
| `MYCMS_MESSAGES_RETENTION` | `12` | Nombre de mois avant la suppression des messages de contact. |

### Mettre à jour MyCMS

```bash
cd /var/www/monsite
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan mycms:install        # applique les nouvelles migrations, garde votre contenu
php artisan optimize
php artisan up
```

---

## Thèmes et langues

- Créer un thème : **[docs/fr/THEMES.md](docs/fr/THEMES.md)** ([English](docs/THEMES.md))
- Traduire MyCMS ou publier un site en plusieurs langues : **[docs/fr/LANGUAGES.md](docs/fr/LANGUAGES.md)** ([English](docs/LANGUAGES.md))

Les deux s'installent depuis l'administration (**Thèmes** / **Langues** → *Installer*)
avec l'adresse d'un dépôt Git public (`https://github.com/quelquun/depot`, `…#v1.2` pour
une version) ou un fichier ZIP.

---

## Développement

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan mycms:install --locale=fr --admin-email=dev@exemple.test
npm run build          # administration + thèmes fournis (versionnés : les serveurs n'ont pas besoin de Node.js)
php artisan serve
php artisan test       # tests automatisés
```

- `npm run dev` — administration avec rechargement à chaud ; `npm run dev:theme` — reconstruit le thème par défaut à chaque modification.
- Style de code : `vendor/bin/pint`.

La structure du projet et les détails de sécurité sont décrits dans le [README anglais](README.md#structure).

## Licence

MyCMS est un logiciel libre publié sous [licence MIT](LICENSE).
Icônes : [Material Symbols](https://fonts.google.com/icons) (Apache 2.0).
Polices : Literata, Nunito Sans (SIL Open Font License).
