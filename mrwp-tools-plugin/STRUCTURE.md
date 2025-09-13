# Structure du Plugin Mr.WordPress Tools

## Arborescence Complète

```
mrwp-tools/
├── mrwp-tools.php              # Bootstrap principal du plugin
├── index.php                   # Protection contre l'accès direct
├── README.md                   # Documentation complète
├── CHANGELOG.md                # Journal des modifications
├── STRUCTURE.md                # Ce fichier
├── TODO.md                     # Suivi des tâches
├── test-plugin.php             # Script de test PHP
├── examples-curl.sh            # Exemples d'utilisation curl
│
├── admin/                      # Interface d'administration
│   ├── index.php              # Protection contre l'accès direct
│   └── Settings.php           # Page de réglages WordPress
│
├── includes/                   # Classes principales
│   ├── index.php              # Protection contre l'accès direct
│   ├── Agent.php              # Classe principale d'orchestration
│   ├── Security.php           # Authentification HMAC-SHA256
│   ├── Rest.php               # Routes API REST
│   ├── Status.php             # Informations système et mises à jour
│   ├── Maintenance.php        # Mode maintenance avec bypass
│   ├── Debug.php              # Mode debug configurable
│   └── Email.php              # Système d'envoi d'emails
│
└── languages/                  # Internationalisation
    ├── index.php              # Protection contre l'accès direct
    └── mrwp-tools.pot         # Template de traduction
```

## Description des Fichiers

### Fichiers Principaux

- **mrwp-tools.php** : Point d'entrée du plugin avec autoloading PSR-4
- **README.md** : Documentation complète avec exemples API
- **CHANGELOG.md** : Journal des versions et modifications

### Classes PHP (namespace MRWP\Agent)

- **Agent.php** : Classe singleton orchestrant tous les composants
- **Security.php** : Gestion de l'authentification HMAC-SHA256
- **Rest.php** : Endpoints API REST (/mrwp/v1/)
- **Status.php** : Collecte des informations système et mises à jour
- **Maintenance.php** : Mode maintenance avec système de bypass
- **Debug.php** : Mode debug sécurisé (log activé, display désactivé)
- **Email.php** : Envoi d'emails avec templates

### Administration

- **Settings.php** : Page de réglages sous "Réglages → Mr.WordPress Tools"

### Scripts de Test

- **test-plugin.php** : Script PHP pour tester toutes les fonctionnalités
- **examples-curl.sh** : Exemples pratiques avec curl

### Sécurité

- **index.php** : Fichiers de protection dans chaque dossier
- Authentification HMAC avec fenêtre temporelle
- Validation et sanitisation des entrées
- Échappement des sorties

## Points d'Entrée API

### Base URL
```
https://site.com/wp-json/mrwp/v1/
```

### Endpoints
- `GET /ping` - Test de connectivité (public)
- `POST /status` - Statut du site (authentifié)
- `POST /action` - Actions système (authentifié)

### Actions Disponibles
- `toggle_maintenance` - Basculer le mode maintenance
- `reset_bypass` - Regénérer le code de bypass
- `toggle_debug` - Basculer le mode debug
- `send_bypass_email` - Envoyer l'email de bypass

## Installation

1. Zipper le dossier `mrwp-tools/`
2. Installer via l'interface WordPress ou FTP
3. Activer le plugin
4. Configurer dans "Réglages → Mr.WordPress Tools"

## Configuration

Le plugin stocke sa configuration dans l'option `mrwp_agent` :

```php
[
    'site_secret' => 'string(64)',        // Clé HMAC
    'hub_url' => 'string',                // URL dashboard externe
    'client_email' => 'string',           // Email propriétaire
    'bypass_code' => 'string(24)',        // Code bypass
    'maintenance_enabled' => 'bool',      // État maintenance
    'debug_enabled' => 'bool'             // État debug
]
```

## Hooks WordPress

- `plugins_loaded` → Initialisation
- `rest_api_init` → Enregistrement API
- `template_redirect` → Vérification maintenance
- `admin_menu` → Page d'administration
- `init` → Composants système

## Compatibilité

- WordPress 6.0+
- PHP 8.0+
- Standards de codage WordPress
- PSR-4 autoloading
- Internationalisation prête