# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Plugin WordPress initial pour la gestion à distance des sites
- API REST sécurisée avec authentification HMAC-SHA256
- Mode maintenance avec système de bypass par cookie
- Mode debug configurable (profil simple)
- Comptage automatique des mises à jour (core, plugins, thèmes)
- Système d'email pour les notifications de maintenance
- Interface d'administration minimaliste
- Support WordPress 6.0+ et PHP 8.0+
- Architecture modulaire avec namespace MRWP\Agent
- Documentation complète avec exemples curl
- Scripts de test pour validation des fonctionnalités
- Support de l'internationalisation
- Sécurité renforcée avec validation des entrées et échappement

### Security
- Authentification HMAC-SHA256 avec fenêtre temporelle (5 minutes)
- Protection contre les attaques replay
- Validation et sanitisation complète des données
- Capacités WordPress respectées (manage_options)
- Protection contre l'accès direct aux fichiers

### API Endpoints
- `GET /mrwp/v1/ping` - Test de connectivité (public)
- `POST /mrwp/v1/status` - Statut complet du site (authentifié)
- `POST /mrwp/v1/action` - Actions système (authentifié)
  - `toggle_maintenance` - Basculer le mode maintenance
  - `reset_bypass` - Regénérer le code de bypass
  - `toggle_debug` - Basculer le mode debug
  - `send_bypass_email` - Envoyer l'email de bypass

### Files Structure
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
├── test-plugin.php         # Script de test PHP
├── examples-curl.sh        # Exemples curl
├── README.md               # Documentation
└── CHANGELOG.md            # Journal des modifications
```