# Créer un thème pour MyCMS

*[English version](../THEMES.md)*

Un thème change **toute l'apparence du site public** (l'administration ne change
jamais). Changer de thème ne touche jamais au contenu : pages, sections, textes et
images restent les mêmes.

MyCMS est livré avec deux thèmes qui servent d'exemples :

| Thème | Dossier | Ce qu'il montre |
|---|---|---|
| **MyCMS Par défaut** | `resources/themes/default` | Un thème complet : tous les gabarits, polices, options, palettes. |
| **Minimal** | `resources/themes/minimal` | Un **thème enfant** : il ne redéfinit que l'en-tête, le pied de page et le grand en-tête, et ajoute ses palettes, ses options et une section personnalisée (« Chiffres clés »). |

Le plus simple pour commencer : **copier `minimal`** et le modifier.

---

## 1. Prérequis

Pour *créer* un thème, il faut :

- connaître **HTML et CSS** ; les gabarits utilisent [Blade](https://laravel.com/docs/blade),
  le langage de gabarits de Laravel (`{{ $variable }}`, `@if`, `@foreach`…) — les bases
  s'apprennent en une heure ;
- un MyCMS installé en local pour essayer le thème (voir le [README](../../README.fr.md)) ;
- *facultatif :* Node.js 20+ pour construire le CSS avec Tailwind CSS comme les thèmes
  fournis. Tout autre outil convient : MyCMS ne charge que les fichiers CSS et JS
  **finaux** listés dans `theme.json`.

Pour *installer* un thème, il suffit de l'administration (Thèmes → Installer un
thème) ou de la commande `php artisan mycms:theme install <adresse>` : aucun outil de
compilation, pas de Node.js sur le serveur.

---

## 2. Structure d'un thème

```
mon-theme/
├── theme.json              ← obligatoire : le manifeste
├── screenshot.png          ← aperçu affiché dans l'administration (svg, png, jpg, webp)
├── views/                  ← obligatoire : gabarits Blade
│   ├── layouts/site.blade.php
│   ├── partials/footer.blade.php
│   ├── site/page.blade.php
│   ├── blocks/hero.blade.php … (un fichier par type de section)
│   └── errors/404.blade.php
├── assets/                 ← CSS/JS compilés, polices, images (servis publiquement)
│   ├── theme.css
│   └── theme.js
└── lang/                   ← facultatif : traductions des textes du thème
    └── fr.json
```

Un thème **ne contient que les gabarits qu'il modifie** : tout gabarit absent est pris
dans le **thème parent** (`"parent"` dans `theme.json`), puis dans le **thème par
défaut**. Un thème enfant peut donc tenir en trois fichiers.

Fichiers copiés à l'installation : `*.blade.php`, `*.json`, `*.css`, `*.js`, `*.map`,
polices (`woff`, `woff2`, `ttf`, `otf`), images (`png`, `jpg`, `jpeg`, `webp`, `gif`,
`svg`, `ico`, `avif`), `*.md`, `*.txt`, `LICENSE`, `README`. Tout le reste (`.git`,
`node_modules`, autres fichiers PHP, configurations de build…) est ignoré.

Seules ces extensions sont servies publiquement depuis le dossier d'un thème :
`css js woff woff2 ttf otf png jpg jpeg webp gif svg ico avif`
(adresse : `/themes/{slug}/{chemin}`). Les gabarits et `theme.json` ne sont jamais servis.

---

## 3. `theme.json`

```json
{
    "name": "Mon thème",
    "slug": "mon-theme",
    "version": "1.0.0",
    "description": "Une courte description affichée dans l'administration.",
    "author": "Votre nom",
    "homepage": "https://github.com/vous/mycms-theme-mon-theme",
    "license": "MIT",
    "parent": "default",
    "screenshot": "screenshot.png",
    "assets": {
        "css": ["assets/theme.css"],
        "js": ["assets/theme.js"]
    },
    "default_palette": "mer",
    "palettes": {
        "mer": {
            "name": "Mer",
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

| Clé | Obligatoire | Description |
|---|---|---|
| `name` | oui | Nom affiché dans l'administration (traduisible via `lang/*.json`). |
| `slug` | oui | Identifiant : minuscules, chiffres et tirets (`mon-theme`). Unique. `default` et `minimal` sont réservés. |
| `version` | non | Affichée dans l'administration. Utilisez le [versionnage sémantique](https://semver.org/lang/fr/). |
| `description`, `author`, `license` | non | Affichés dans l'administration. |
| `homepage` | non | Lien `https://` affiché dans l'administration. |
| `parent` | non | Slug du thème dont on hérite les gabarits manquants (en général `default`). |
| `screenshot` | non | Image d'aperçu (par défaut `screenshot.png`). Taille conseillée 1600 × 1000. |
| `assets.css`, `assets.js` | non | Fichiers chargés sur chaque page, relatifs au dossier du thème. |
| `palettes` | non | Palettes de couleurs proposées dans **Couleurs & style** (voir §6). |
| `default_palette` | non | Palette appliquée à la première activation du thème. |
| `options` | non | Réglages proposés dans **Couleurs & style → Options du thème** (voir §7). |
| `blocks` | non | Nouveaux types de sections ajoutés par le thème (voir §8). |

---

## 4. Gabarits

Tous les gabarits publics sont dans l'espace de vues **`theme::`**. MyCMS affiche une
page avec `theme::site.page`, qui étend `theme::layouts.site` et inclut un
`theme::blocks.{type}` par section.

| Gabarit | Rôle |
|---|---|
| `layouts/site.blade.php` | La page HTML : `<head>`, en-tête, menu, `@yield('content')`, pied de page. |
| `site/page.blade.php` | Boucle sur les sections de la page (rarement redéfini). |
| `partials/footer.blade.php` | Pied de page (inclus par le gabarit par défaut). |
| `blocks/{type}.blade.php` | Un gabarit par type de section (liste ci-dessous). |
| `blocks/partials/*.blade.php` | Morceaux partagés : `heading`, `buttons`, `breadcrumb`, `contact-form`, `cta-extras`. |
| `errors/404.blade.php`, `errors/error.blade.php` | Pages d'erreur (« page introuvable », 403, 419, 429). |

Types de sections de MyCMS : `hero`, `page_header`, `cards`, `text_features`,
`text_image`, `steps`, `price_banner`, `timeline`, `quote`, `callout`, `pricing`,
`info_columns`, `faq`, `gallery`, `video`, `cta`, `contact_cards`, `map`,
`contact_form`, `rich_text`. Leurs champs sont décrits dans
`app/Cms/BlockRegistry.php` ; lisez le gabarit du thème par défaut avant de redéfinir
une section.

### Variables disponibles dans tous les gabarits

| Variable | Contenu |
|---|---|
| `$settings` | Informations du site : `$settings->get('phone')`, `siteName()`, `fullAddress()`, `phoneHref()`, `hours()`, `socialLinks()`, `announcementActive()`, `mapEmbedUrl()`… Les clés sont listées dans `app/Cms/SiteSettings.php`. |
| `$ph` | Remplacement des balises : `$ph->text($texte)` (HTML sûr), `$ph->raw($texte)` (attributs). |
| `$r` | Outils d'affichage, voir ci-dessous. |
| `$theme` | Le thème actif : `$theme->styles()`, `$theme->scripts()`, `$theme->assetUrl('img/logo.svg')`. |
| `$page` | La page courante (`title`, `url()`, `path()`, `is_home`…) — `null` sur les pages d'erreur. |
| `$navPages`, `$footerPages` | Pages du menu principal et du pied de page (langue courante). |
| `$homeUrl` | Adresse de l'accueil dans la langue courante. |
| `$ctaLabel`, `$ctaUrl` | Bouton principal de l'en-tête (vides s'il est désactivé). |
| `$language`, `$languages`, `$contentLocale` | Langue courante (`htmlLang()`, `direction()`, `nativeName()`), toutes les langues, code courant. |
| `$alternates` | `[code => url]` de la même page dans les autres langues (menu de langue, `hreflang`). |
| `$preview` | `true` quand la page est prévisualisée depuis l'administration. |
| `$cspNonce` | Nonce à placer sur vos balises `<style>` / `<script>` en ligne. |

Dans un gabarit de section, vous avez aussi **`$d`** (les données de la section, tous
les champs toujours présents) et **`$block`** (le modèle).

### Outils d'affichage (`$r`)

| Outil | Usage |
|---|---|
| `{!! $r->t($d['title']) !!}` | Texte simple → HTML sûr (balises comme `{phone}` remplacées, retours à la ligne conservés). **À utiliser pour tous les champs texte.** |
| `{!! $r->h($d['text']) !!}` | Texte mis en forme (champs « rich »), déjà nettoyé à l'enregistrement. |
| `{!! $r->quote($d['quote']) !!}` | Texte entre les guillemets de la langue courante. |
| `$r->img($d['image'])` | Image (`url()`, `alt`, `width`, `height`) ou `null`. |
| `{!! $r->linkAttrs($d['url']) !!}` | `href="…"` (+ `target="_blank"` pour les liens externes). Gère `{phone}`, `{email}`, `{button}` et le préfixe de langue. |
| `$r->href($url)` | Seulement l'adresse. |
| `$r->lines($texte)` | Lignes d'un champ multiligne (listes à puces). |
| `$r->background(...)`, `$r->tone($ton, 'soft')`, `$r->columns($n)` | Classes Tailwind prêtes à l'emploi, utilisées par le thème par défaut. |
| `$r->videoEmbed($url)` | Adresse d'intégration YouTube / Vimeo respectueuse de la vie privée, ou `null`. |

Autres outils : `<x-icon name="call" class="text-[20px]" />` insère une icône de
`resources/icons` (Material Symbols) ; `theme_option('nom')` lit une option du thème ;
`__('Text')` traduit un texte (voir §9).

> **Sécurité** — N'affichez jamais un champ avec `{!! $d['…'] !!}` directement :
> passez toujours par `$r->t()`, `$r->h()` ou `{{ }}`. Les champs texte sont du texte
> simple ; seuls les champs « rich » contiennent du HTML (nettoyé).

---

## 5. Styles, scripts et politique de sécurité

Le site public envoie une **Content-Security-Policy** stricte :

- scripts : fichiers du thème, ou `<script nonce="{{ $cspNonce }}">` en ligne ;
- styles : fichiers du thème, ou `<style nonce="{{ $cspNonce }}">` en ligne ;
  **les attributs `style="…"` sont bloqués** ;
- polices et images : le thème, le site, les URI `data:` ;
- aucune ressource externe (Google Fonts, CDN…) : **hébergez vos polices dans le thème** ;
- iframes : uniquement OpenStreetMap, YouTube (nocookie) et Vimeo.

Listez vos fichiers dans `assets.css` / `assets.js` ; le gabarit les charge avec
`$theme->styles()` / `$theme->scripts()`, avec un numéro de version qui change avec le
fichier (les caches des navigateurs se mettent à jour tout seuls).

### Construire avec Tailwind CSS (comme les thèmes fournis)

Les thèmes fournis sont construits par `vite.theme.config.js` :

```bash
npx vite build -c vite.theme.config.js --mode mon-theme   # construit resources/themes/mon-theme
```

Votre `theme.js` importe votre `theme.css` ; les lignes `@source` indiquent à Tailwind
où trouver les classes (vos gabarits, et ceux du thème parent) :

```css
@import 'tailwindcss';
@import './palette.css';      /* à copier depuis le thème par défaut */
@source './views';
@source '../default/views';   /* thème enfant : classes des gabarits hérités */
@source '../../../app/Cms';
```

Pour un thème développé dans **son propre dépôt**, un `package.json` minimal
(`vite`, `tailwindcss`, `@tailwindcss/vite`) et la même configuration Vite suffisent.
**Versionnez le dossier `assets/` compilé** : les sites installent les thèmes sans les
compiler.

---

## 6. Couleurs

L'utilisateur choisit 9 couleurs dans **Couleurs & style** (ou l'une de vos palettes).
MyCMS en déduit toutes les nuances et les expose en variables CSS `--c-{nom}` (valeurs
`r g b`) :

`primary`, `on-primary`, `primary-container`, `primary-fixed`, `on-primary-fixed`…,
`secondary…`, `tertiary…` (couleur d'accentuation), `btn`, `on-btn`, `btn-hover`,
`surface`, `surface-container-lowest|low|(aucun)|high|highest`, `on-surface`,
`on-surface-variant`, `outline-variant`, `error…`.

`palette.css` (dans le thème par défaut) les associe aux couleurs Tailwind :
`bg-primary`, `text-on-surface`, `bg-btn`, `bg-surface-container-low`… Sans Tailwind,
utilisez-les directement : `color: rgb(var(--c-primary));`.

Associez toujours un fond à sa couleur `on-` (`bg-btn text-on-btn`) : elle est calculée
pour rester lisible quel que soit le choix de l'utilisateur, palettes sombres comprises.

---

## 7. Options

Les options apparaissent dans **Couleurs & style → Options du thème** :

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

Types : `text`, `textarea`, `toggle`, `select`, `color`, `media` (identifiant d'une
image : `$r->img(theme_option('hero_image'))`). Lisez-les avec
`theme_option('header_layout')`. Les valeurs sont enregistrées par thème : revenir à un
thème retrouve ses options.

---

## 8. Sections personnalisées

Un thème peut ajouter des types de sections. Déclarez les champs dans `theme.json` et
créez `views/blocks/{type}.blade.php` :

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

- `type` (section) : minuscules, chiffres, `_` ; `icon` : un nom de `resources/icons`.
- Types de champs : `text`, `textarea`, `rich`, `image`, `icon`, `select` (avec
  `options`), `toggle`, `link`, `number`, `repeater` (avec `fields`, 2 niveaux max),
  `heading`.
- Clés d'un champ : `name`, `label`, `type`, et en option `default`, `required`, `max`,
  `help`, `max_items`, `item_label`, `add_label`.

Le formulaire de l'administration, la validation et le nettoyage sont générés
automatiquement. Si un autre thème est activé, la section reste en base mais n'est pas
affichée (l'administration indique « non disponible avec le thème actuel »).

---

## 9. Traductions

Écrivez les textes du thème **en anglais** avec `__('Text')` et ajoutez des fichiers
`lang/{code}.json` :

```json
{ "Read more": "Lire la suite", "Header layout": "Disposition de l'en-tête" }
```

Les libellés de `theme.json` (nom, description, options, sections) sont traduits par
les mêmes fichiers. Les textes déjà traduits par MyCMS (`__('Contact')`…) n'ont pas
besoin d'être répétés. Détails : [LANGUAGES.md](LANGUAGES.md).

---

## 10. Publier et installer

1. Placez le thème dans un **dépôt Git public**, `theme.json` à la racine (GitHub,
   GitLab, Codeberg…), avec le dossier `assets/` compilé versionné.
2. Étiquetez vos versions (`git tag v1.0.0 && git push --tags`).
3. Installation depuis **Thèmes → Installer un thème** :
   - `https://github.com/vous/mycms-theme-mon-theme` (branche par défaut),
   - `https://github.com/vous/mycms-theme-mon-theme#v1.0.0` (une version ou une branche),
   - un lien vers un `.zip`, ou un fichier `.zip` envoyé (`theme.json` à la racine ou
     dans un unique dossier, comme dans les archives GitHub).
4. Ou en ligne de commande :
   ```bash
   php artisan mycms:theme install https://github.com/vous/mycms-theme-mon-theme --activate
   php artisan mycms:theme update mon-theme
   php artisan mycms:theme list
   ```

Les thèmes installés sont rangés dans `storage/app/themes/{slug}`. Un thème installé
depuis une adresse se **met à jour** en un clic.

> **Confiance** — Un thème contient des gabarits Blade, c'est-à-dire du code PHP
> exécuté par le serveur. N'installez que des thèmes de sources de confiance.
> L'administrateur du serveur peut interdire l'installation depuis l'administration
> avec `MYCMS_ALLOW_PACKAGE_INSTALL=false`.

---

## 11. Liste de contrôle avant publication

- [ ] `theme.json` est un JSON valide, avec un `slug` unique et une `version`.
- [ ] Tous les types de pages fonctionnent : accueil, page de texte, contact (formulaire
      envoyé / avec erreurs), 404.
- [ ] Menu mobile, menu de langue (activez une deuxième langue), bandeau d'annonce.
- [ ] Une palette sombre reste lisible (`Night` ou la vôtre).
- [ ] Aucune ressource externe, aucun attribut `style="…"`, aucun script en ligne sans
      nonce (console du navigateur : aucune erreur CSP).
- [ ] Textes en anglais avec `__()`, et `lang/*.json` pour les langues prises en charge.
- [ ] `assets/` compilé et versionné ; `screenshot.png` présent.
