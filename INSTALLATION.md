# Installation du Plugin Mr.WordPress Tools

## 🚀 Installation Rapide

### Méthode 1: Via l'Interface WordPress (Recommandée)

1. **Téléchargez** le fichier `mrwp-tools-v1.0.0.zip`
2. **Connectez-vous** à votre administration WordPress
3. Allez dans **Extensions → Ajouter une extension**
4. Cliquez sur **"Téléverser une extension"**
5. **Sélectionnez** le fichier ZIP téléchargé
6. Cliquez sur **"Installer maintenant"**
7. **Activez** le plugin après l'installation

### Méthode 2: Via FTP

1. **Téléchargez** et **décompressez** le fichier `mrwp-tools-v1.0.0.zip`
2. **Uploadez** le dossier `mrwp-tools-plugin` vers `/wp-content/plugins/`
3. **Renommez** le dossier en `mrwp-tools`
4. **Connectez-vous** à votre administration WordPress
5. Allez dans **Extensions** et **activez** "Mr.WordPress Tools"

## ⚙️ Configuration Post-Installation

### Étape 1: Accéder aux Réglages
- Allez dans **Réglages → Mr.WordPress Tools**
- La page de configuration s'ouvre

### Étape 2: Configuration de Base
Remplissez les champs suivants :

- **Hub URL** : URL de votre dashboard externe (optionnel)
  ```
  Exemple: https://dashboard.monsite.com
  ```

- **Client Email** : Email du propriétaire du site
  ```
  Exemple: admin@monsite.com
  ```

### Étape 3: Récupérer les Informations API

Dans la section **"API Information"**, vous trouverez :

- **API Base URL** : `https://votre-site.com/wp-json/mrwp/v1/`
- **Site Secret** : Clé secrète pour l'authentification HMAC (partiellement masquée)
- **Bypass Link** : Lien d'accès au site en mode maintenance

**Important** : Notez le **Site Secret complet** - il est nécessaire pour l'authentification API.

## 🔧 Test de l'Installation

### Test 1: Endpoint Public
Testez la connectivité avec cette commande :

```bash
curl -X GET "https://votre-site.com/wp-json/mrwp/v1/ping"
```

**Réponse attendue :**
```json
{
  "ok": true,
  "site": "https://votre-site.com",
  "name": "Nom de votre site",
  "version": "1.0.0"
}
```

### Test 2: Script de Test PHP
Utilisez le script fourni `test-plugin.php` :

1. **Éditez** le script :
   ```php
   $site_url = 'https://votre-site.com';
   $site_secret = 'votre_site_secret_64_caracteres';
   ```

2. **Exécutez** le script :
   ```bash
   php test-plugin.php
   ```

### Test 3: Exemples curl
Utilisez le script bash fourni `examples-curl.sh` :

1. **Éditez** le script :
   ```bash
   SITE_URL="https://votre-site.com"
   SITE_SECRET="votre_site_secret_64_caracteres"
   ```

2. **Exécutez** le script :
   ```bash
   chmod +x examples-curl.sh
   ./examples-curl.sh
   ```

## 🔐 Sécurité et Authentification

### Récupération du Site Secret
1. Connectez-vous à l'admin WordPress
2. Allez dans **Réglages → Mr.WordPress Tools**
3. Dans **"API Information"**, copiez le site secret complet
4. **Gardez-le secret** - ne le partagez jamais publiquement

### Authentification HMAC
L'API utilise l'authentification HMAC-SHA256. Exemple de calcul :

```bash
timestamp=$(date +%s)
body='{"action":"toggle_maintenance"}'
message="${timestamp}\n${body}"
signature=$(echo -n "$message" | openssl dgst -sha256 -hmac "$SITE_SECRET" -binary | base64)
```

## 📋 Actions Disponibles

Une fois configuré, l'API supporte ces actions :

- **`toggle_maintenance`** : Active/désactive le mode maintenance
- **`reset_bypass`** : Régénère le code de bypass
- **`toggle_debug`** : Active/désactive le mode debug
- **`send_bypass_email`** : Envoie l'email de bypass au client

## 🛟 Dépannage

### Plugin Non Visible
- Vérifiez que le plugin est dans `/wp-content/plugins/mrwp-tools/`
- Vérifiez les permissions de fichiers (644 pour les fichiers, 755 pour les dossiers)

### Erreur API 404
- Allez dans **Réglages → Permaliens** et cliquez **"Enregistrer"**
- Vérifiez que les règles de réécriture sont actives

### Erreur d'Authentification
- Vérifiez que le site secret est correct
- Vérifiez le calcul de la signature HMAC
- Vérifiez que le timestamp est dans la fenêtre de 5 minutes

### Mode Maintenance Bloquant
En cas de problème avec le mode maintenance :

1. **Via base de données** :
   ```sql
   UPDATE wp_options 
   SET option_value = REPLACE(option_value, '"maintenance_enabled";b:1', '"maintenance_enabled";b:0') 
   WHERE option_name = 'mrwp_agent';
   ```

2. **Via FTP** : Désactivez temporairement le plugin

## 📞 Support

- **Documentation** : Consultez le `README.md` complet
- **Tests** : Utilisez les scripts `test-plugin.php` et `examples-curl.sh`
- **Structure** : Consultez `STRUCTURE.md` pour l'architecture
- **Changelog** : Consultez `CHANGELOG.md` pour les versions

---

**Mr.WordPress Tools v1.0.0** - Plugin professionnel pour la gestion à distance des sites WordPress