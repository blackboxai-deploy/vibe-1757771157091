# Mr.WordPress Tools - Plugin Agent

Plugin WordPress professionnel permettant l'exposition d'une API REST sécurisée pour la gestion à distance des sites WordPress.

## 🚀 Fonctionnalités

- **API REST sécurisée** avec authentification HMAC-SHA256
- **Mode maintenance** avec système de bypass par cookie
- **Mode debug** configurable (profil simple)
- **Comptage des mises à jour** (core, plugins, thèmes)
- **Système d'email** pour les notifications
- **Interface d'administration minimaliste**
- **Compatibilité** WordPress 6.0+ et PHP 8.0+

## 📋 Installation

1. Téléchargez le plugin depuis les releases
2. Uploadez le dossier `mrwp-tools` dans `/wp-content/plugins/`
3. Activez le plugin dans l'interface d'administration WordPress
4. Configurez les réglages dans "Réglages → Mr.WordPress Tools"

### Configuration Initiale

Après activation, le plugin génère automatiquement :
- Un **site secret** (64 caractères) pour l'authentification HMAC
- Un **bypass code** (24 caractères) pour le mode maintenance

## ⚙️ Configuration

Accédez à **Réglages → Mr.WordPress Tools** pour configurer :

- **Hub URL** : URL du dashboard externe qui gérera ce site
- **Client Email** : Email du propriétaire qui recevra les notifications
- **Bypass Link** : Lien d'accès au site en mode maintenance (lecture seule)

## 🔒 Authentification API

L'API utilise l'authentification HMAC-SHA256 pour sécuriser les endpoints.

### Headers Requis

```bash
x-mrwp-timestamp: 1640995200  # Timestamp Unix (epoch seconds)
x-mrwp-signature: abc123...   # Signature HMAC-SHA256
```

### Calcul de la Signature

```bash
message = timestamp + "\n" + body
signature = hmac_sha256(message, site_secret)
```

### Exemple PHP

```php
<?php
$timestamp = time();
$body = '{"action":"toggle_maintenance"}';
$site_secret = 'votre_site_secret_64_caracteres';

$message = $timestamp . "\n" . $body;
$signature = hash_hmac('sha256', $message, $site_secret);

$headers = [
    'Content-Type: application/json',
    'x-mrwp-timestamp: ' . $timestamp,
    'x-mrwp-signature: ' . $signature
];
?>
```

## 🛠️ Endpoints API

### Base URL
```
https://votre-site.com/wp-json/mrwp/v1/
```

---

### `GET /ping`
Test de connectivité (endpoint public)

**Exemple :**
```bash
curl -X GET "https://site.com/wp-json/mrwp/v1/ping"
```

**Réponse :**
```json
{
  "ok": true,
  "site": "https://site.com",
  "name": "Mon Site WordPress",
  "version": "1.0.0"
}
```

---

### `POST /status`
Récupération du statut complet du site (authentifié)

**Exemple :**
```bash
timestamp=$(date +%s)
body='{}'
signature=$(echo -n "${timestamp}\n${body}" | openssl dgst -sha256 -hmac "SITE_SECRET" -binary | base64)

curl -X POST "https://site.com/wp-json/mrwp/v1/status" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${signature}" \
  -d "${body}"
```

**Réponse :**
```json
{
  "site_name": "Mon Site WordPress",
  "home_url": "https://site.com",
  "wp_version": "6.4.0",
  "php_version": "8.2.0",
  "core_updates_count": 0,
  "plugin_updates_count": 3,
  "theme_updates_count": 1,
  "maintenance_enabled": false,
  "debug_enabled": false,
  "bypass_link": "https://site.com/?bypass_code=abc123...",
  "last_synced_at": 1640995200
}
```

---

### `POST /action`
Exécution d'actions système (authentifié)

**Body requis :**
```json
{
  "action": "nom_action"
}
```

**Actions disponibles :**
- `toggle_maintenance` - Basculer le mode maintenance
- `reset_bypass` - Regénérer le code de bypass
- `toggle_debug` - Basculer le mode debug
- `send_bypass_email` - Envoyer l'email de bypass

#### Exemple : Activer le mode maintenance

```bash
timestamp=$(date +%s)
body='{"action":"toggle_maintenance"}'
signature=$(echo -n "${timestamp}\n${body}" | openssl dgst -sha256 -hmac "SITE_SECRET" -binary | base64)

curl -X POST "https://site.com/wp-json/mrwp/v1/action" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${signature}" \
  -d "${body}"
```

**Réponse :**
```json
{
  "ok": true,
  "action": "toggle_maintenance",
  "state": {
    "maintenance_enabled": true,
    "bypass_link": "https://site.com/?bypass_code=abc123..."
  }
}
```

#### Exemple : Regénérer le bypass

```bash
timestamp=$(date +%s)
body='{"action":"reset_bypass"}'
signature=$(echo -n "${timestamp\n${body}" | openssl dgst -sha256 -hmac "SITE_SECRET" -binary | base64)

curl -X POST "https://site.com/wp-json/mrwp/v1/action" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${signature}" \
  -d "${body}"
```

**Réponse :**
```json
{
  "ok": true,
  "action": "reset_bypass",
  "state": {
    "bypass_link": "https://site.com/?bypass_code=nouveau_code...",
    "bypass_code": "nouveau_code_24_caracteres"
  }
}
```

## 🔧 Mode Maintenance

Quand le mode maintenance est activé :

- **Tous les visiteurs** voient une page maintenance personnalisée
- **Utilisateurs admin connectés** avec capacité `manage_options` peuvent accéder au site
- **Zone d'administration** (`/wp-admin/`) reste accessible
- **API endpoints** continuent de fonctionner
- **Système de bypass** permet l'accès via cookie ou paramètre URL

### Utilisation du Bypass

1. **Via URL :** `https://site.com/?bypass_code=CODE_BYPASS`
2. **Via Cookie :** Le cookie `mrwp_bypass` est automatiquement défini (durée 24h)

## 🐛 Mode Debug

Le mode debug active un profil simple et sécurisé :

- `WP_DEBUG = true` - Active les erreurs PHP
- `WP_DEBUG_LOG = true` - Enregistre les erreurs dans un fichier
- `WP_DEBUG_DISPLAY = false` - Masque les erreurs côté front (sécurité)

**Fichier de log :** `/wp-content/debug.log`

## 📧 Système d'Email

### Template de l'email de bypass

```
Sujet: [Mr.WordPress] Maintenance activée – {site_name}

Bonjour,

Nous venons d'activer un mode maintenance pour intervenir en sécurité sur le site {site_name}.

Accès privé (ne pas partager) : {bypass_link}

Date/heure : {current_datetime}
Contact : support@mrwordpress.com

— Mr.WordPress Tools
```

## 🗄️ Stockage des Données

Le plugin utilise une option WordPress unique : `mrwp_agent`

```php
[
    'site_secret' => 'string(64)',        // Clé HMAC
    'hub_url' => 'string',                // URL dashboard externe
    'client_email' => 'string',           // Email propriétaire
    'bypass_code' => 'string(24)',        // Code bypass maintenance
    'maintenance_enabled' => 'bool',      // État maintenance
    'debug_enabled' => 'bool'             // État debug
]
```

## 🔐 Sécurité

- **Authentification HMAC-SHA256** avec fenêtre temporelle (5 minutes)
- **Validation des timestamps** pour prévenir les attaques replay
- **Sanitisation complète** des données d'entrée
- **Échappement** approprié pour la sortie
- **Capacités WordPress** respectées (`manage_options`)
- **Nonces** pour les formulaires admin

## 🛠️ Développement

### Structure des Fichiers

```
mrwp-tools/
├── mrwp-tools.php          # Bootstrap principal
├── admin/
│   └── Settings.php        # Page réglages admin
├── includes/
│   ├── Agent.php           # Classe principale
│   ├── Security.php        # Authentification HMAC
│   ├── Rest.php            # Routes API REST
│   ├── Status.php          # Informations système
│   ├── Maintenance.php     # Mode maintenance
│   ├── Debug.php           # Mode debug
│   └── Email.php           # Système email
├── languages/              # Fichiers de traduction
└── README.md              # Documentation
```

### Hooks WordPress Utilisés

- `plugins_loaded` - Initialisation du plugin
- `rest_api_init` - Enregistrement des routes API
- `template_redirect` - Vérification mode maintenance
- `admin_menu` - Page d'administration
- `init` - Initialisation des composants

## 🌐 Internationalisation

Le plugin est prêt pour la traduction avec le domaine textuel `mrwp-tools`.

## 📄 Licence

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## 🤝 Support

Pour le support technique : support@mrwordpress.com

---

**Mr.WordPress Tools v1.0.0**  
Plugin professionnel pour la gestion à distance des sites WordPress