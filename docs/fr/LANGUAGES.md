# Les langues dans MyCMS

*[English version](../LANGUAGES.md)*

MyCMS gère deux choses différentes :

1. **La langue de l'interface** — les textes de MyCMS lui-même : administration,
   boutons et messages du site public (« Envoyer mon message », « Accueil »…), e-mails,
   messages d'erreur. Ils viennent des **packs de langue**.
2. **La langue de votre contenu** — vos pages et les informations de votre site. Vous
   les écrivez vous-même ; un site peut être publié en plusieurs langues (voir §6).

**L'anglais** est la langue source de MyCMS (toujours disponible). **Le français** est
fourni. Toute autre langue s'installe depuis un dépôt Git ou un fichier ZIP.

---

## 1. Prérequis

Pour *créer* un pack de langue, il faut :

- un éditeur de texte qui enregistre en **UTF-8** (VS Code, Notepad++, Sublime Text…) ;
- connaître le format JSON (`"clé": "valeur"`, virgules, guillemets échappés en `\"`) ;
- *conseillé :* un MyCMS local pour générer le modèle et vérifier le travail
  (`php artisan mycms:translations`, voir §3). Aucune programmation n'est nécessaire.

Un pack de langue ne contient **que des fichiers JSON** : il ne peut exécuter aucun
code, son installation est donc toujours sans danger.

---

## 2. Structure d'un pack de langue

```
mycms-lang-de/
├── language.json      ← obligatoire : le manifeste
├── messages.json      ← textes de l'interface (« texte anglais » → traduction)
├── validation.json    ← messages d'erreur des formulaires (facultatif mais conseillé)
├── passwords.json     ← messages de réinitialisation du mot de passe (facultatif)
├── pagination.json    ← « Précédent » / « Suivant » (facultatif)
└── auth.json          ← messages de connexion (facultatif)
```

Copiez `resources/languages/fr` comme point de départ : c'est un pack complet.

### `language.json`

```json
{
    "code": "de",
    "name": "German",
    "native": "Deutsch",
    "version": "1.0.0",
    "author": "Votre nom",
    "direction": "ltr",
    "og_locale": "de_DE"
}
```

| Clé | Obligatoire | Description |
|---|---|---|
| `code` | oui | Code de langue : `de`, `es`, `it`… ou avec une région `pt_BR`, `zh_TW` (lettres + `_` + région). Utilisé dans les adresses du site : `/de/…`, `/pt-br/…`. `en` et les langues fournies ne peuvent pas être remplacées. |
| `name` | oui | Nom de la langue en anglais. |
| `native` | non | Nom dans la langue elle-même, affiché dans les menus (« Deutsch »). |
| `direction` | non | `ltr` (par défaut) ou `rtl` pour l'arabe, l'hébreu, le persan… |
| `og_locale` | non | Valeur pour les réseaux sociaux (`de_DE`). |
| `version`, `author` | non | Affichés dans l'administration. |

### `messages.json`

Les clés sont les **textes anglais exacts** de MyCMS, les valeurs vos traductions :

```json
{
    "Save": "Speichern",
    "Hello :name": "Hallo :name",
    ":count page|:count pages": ":count Seite|:count Seiten",
    "Welcome to {site_name}": "Willkommen bei {site_name}",
    "“:text”": "„:text“"
}
```

Règles :

- **Gardez les paramètres** qui commencent par `:` (`:name`, `:count`, `:title`…) — vous
  pouvez les déplacer dans la phrase.
- **Gardez les balises** entre accolades (`{site_name}`, `{email}`, `{phone}`…) : elles
  sont remplacées par les informations du site.
- **Pluriels** : `singulier|pluriel` séparés par `|` (autant de formes qu'en anglais
  suffisent ; règles avancées : [pluralisation Laravel](https://laravel.com/docs/localization#pluralization)).
- Quelques clés sont des **adresses** des pages de départ : `about`, `services`,
  `contact`, `legal-notice`, `privacy-policy`. Traduisez-les en minuscules séparées par
  des tirets, sans accents (`a-propos`, `mentions-legales`…).
- `“:text”` donne les guillemets de votre langue.
- Une clé manquante affiche simplement le texte anglais : un pack partiel est utilisable.

### Fichiers de groupe (`validation.json`…)

Même contenu que les fichiers de traduction PHP de Laravel, en JSON. Reprenez ceux de
`resources/languages/fr/*.json`, ou ceux du projet communautaire
[Laravel-Lang](https://github.com/Laravel-Lang/lang) (convertissez les tableaux PHP en JSON).

---

## 3. Générer le modèle et vérifier son travail

Sur un MyCMS local :

```bash
# Écrit storage/app/translations/de/messages.json avec tous les textes à traduire
# (valeurs vides) et un language.json à compléter
php artisan mycms:translations de --template

# Après installation du pack : liste les textes manquants et obsolètes
php artisan mycms:translations de
```

`--template` conserve les traductions déjà présentes dans le pack installé : relancez-le
après chaque mise à jour de MyCMS pour obtenir les nouveaux textes.

---

## 4. Thèmes et traductions

Les thèmes contiennent aussi des textes. Pour chaque langue, MyCMS fusionne :

1. les fichiers `lang/{code}.json` des thèmes,
2. puis `messages.json` du pack de langue (il l'emporte sur les thèmes),
3. puis `lang/{code}.json` à la racine du site, si vous en créez un (il l'emporte sur
   tout : pratique pour ajuster un seul texte sur votre site sans toucher au pack).

Un pack de langue peut donc aussi traduire les textes des thèmes fournis. Voir
[THEMES.md](THEMES.md#9-traductions) pour traduire votre propre thème.

---

## 5. Publier et installer

1. Placez les fichiers dans un **dépôt Git public** (`language.json` à la racine), par
   exemple `mycms-lang-de`. Étiquetez vos versions (`v1.0.0`).
2. Installez-le depuis **Langues → Installer une langue** : adresse du dépôt
   (`https://github.com/vous/mycms-lang-de`, ou `…#v1.0.0` pour une version), lien vers
   un `.zip`, ou fichier `.zip` envoyé.
3. Ou en ligne de commande :
   ```bash
   php artisan mycms:language install https://github.com/vous/mycms-lang-de
   php artisan mycms:language update de
   php artisan mycms:language list
   ```

Les packs installés sont rangés dans `storage/app/languages/{code}`.

Pour proposer votre traduction à tout le monde, ouvrez une *pull request* qui l'ajoute
dans `resources/languages/` du dépôt MyCMS.

---

## 6. Publier un site en plusieurs langues

1. **Langues** : cochez les langues du site, choisissez la langue par défaut, enregistrez.
   - La langue par défaut est servie à la racine : `/`, `/a-propos`.
   - Les autres sous un préfixe : `/en`, `/en/about`.
2. **Pages** : ouvrez une page et cliquez sur **Traduire en…**. Une copie liée est créée
   dans l'autre langue : traduisez son titre, son adresse et chaque section, puis
   publiez-la.
3. **Informations du site** : choisissez la langue en haut de l'écran pour traduire le
   nom, le slogan, le bouton principal, le pied de page, l'annonce… Les champs vides
   reprennent la langue par défaut.

Les visiteurs ont un menu de langue ; les moteurs de recherche reçoivent des liens
`hreflang` et un plan du site avec toutes les langues.

La **langue de l'administration** se choisit par chaque administrateur dans **Mon
compte** (la valeur par défaut se règle dans **Langues**).
