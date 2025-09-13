<?php
/**
 * Générateur de téléchargement sécurisé pour le plugin Mr.WordPress Tools
 * 
 * Ce script génère un lien de téléchargement sécurisé pour le fichier ZIP du plugin
 */

// Configuration
$zip_file = 'mrwp-tools-v1.0.0.zip';
$plugin_name = 'Mr.WordPress Tools v1.0.0';
$download_filename = 'mrwp-tools-v1.0.0.zip';

// Vérifier que le fichier existe
if (!file_exists($zip_file)) {
    die("❌ Erreur : Le fichier ZIP n'existe pas.\n");
}

// Informations du fichier
$file_size = filesize($zip_file);
$file_hash = hash_file('sha256', $zip_file);
$file_modified = date('Y-m-d H:i:s', filemtime($zip_file));

echo "🎉 Plugin WordPress Prêt au Téléchargement !\n\n";

echo "📦 Informations du Package\n";
echo "================================\n";
echo "Nom: $plugin_name\n";
echo "Fichier: $download_filename\n";
echo "Taille: " . number_format($file_size / 1024, 2) . " KB\n";
echo "Modifié: $file_modified\n";
echo "SHA256: $file_hash\n\n";

echo "📋 Contenu du Plugin\n";
echo "================================\n";
echo "✅ mrwp-tools.php - Bootstrap principal\n";
echo "✅ includes/ - 7 classes PHP professionnelles\n";
echo "   ├── Agent.php - Orchestration générale\n";
echo "   ├── Security.php - Authentification HMAC-SHA256\n";
echo "   ├── Rest.php - API REST endpoints\n";
echo "   ├── Status.php - Informations système\n";
echo "   ├── Maintenance.php - Mode maintenance avec bypass\n";
echo "   ├── Debug.php - Mode debug configurable\n";
echo "   └── Email.php - Système d'envoi d'emails\n";
echo "✅ admin/ - Interface d'administration WordPress\n";
echo "✅ languages/ - Support de traduction (POT)\n";
echo "✅ Documentation complète (README, INSTALLATION, etc.)\n";
echo "✅ Scripts de test (PHP + curl)\n\n";

echo "🚀 Instructions d'Installation\n";
echo "================================\n";
echo "1. Téléchargez le fichier: $download_filename\n";
echo "2. Connectez-vous à votre admin WordPress\n";
echo "3. Allez dans Extensions → Ajouter une extension\n";
echo "4. Cliquez 'Téléverser une extension'\n";
echo "5. Sélectionnez le fichier ZIP et installez\n";
echo "6. Activez le plugin\n";
echo "7. Configurez dans Réglages → Mr.WordPress Tools\n\n";

echo "🔧 Configuration Post-Installation\n";
echo "================================\n";
echo "• Hub URL: URL de votre dashboard externe\n";
echo "• Client Email: Email du propriétaire du site\n";
echo "• Récupérez le Site Secret pour l'API\n";
echo "• Testez avec les scripts fournis\n\n";

echo "🔗 API Endpoints Disponibles\n";
echo "================================\n";
echo "• GET /mrwp/v1/ping - Test de connectivité\n";
echo "• POST /mrwp/v1/status - Statut du site\n";
echo "• POST /mrwp/v1/action - Actions système\n";
echo "  ├── toggle_maintenance - Mode maintenance\n";
echo "  ├── reset_bypass - Nouveau code bypass\n";
echo "  ├── toggle_debug - Mode debug\n";
echo "  └── send_bypass_email - Email de bypass\n\n";

echo "🛡️ Sécurité\n";
echo "================================\n";
echo "• Authentification HMAC-SHA256\n";
echo "• Fenêtre temporelle de 5 minutes\n";
echo "• Validation complète des entrées\n";
echo "• Protection contre l'accès direct\n\n";

// Fonction de téléchargement sécurisé (pour utilisation web)
if (php_sapi_name() !== 'cli') {
    if (isset($_GET['download']) && $_GET['download'] === 'true') {
        // Headers pour téléchargement sécurisé
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $download_filename . '"');
        header('Content-Length: ' . $file_size);
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Lecture et envoi du fichier
        readfile($zip_file);
        exit;
    }
    
    echo "<h2>Téléchargement Web</h2>\n";
    echo "<p><a href='?download=true' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>📥 Télécharger $plugin_name</a></p>\n";
}

echo "💡 Support et Documentation\n";
echo "================================\n";
echo "• README.md - Guide complet d'utilisation\n";
echo "• INSTALLATION.md - Instructions détaillées\n";
echo "• test-plugin.php - Script de test complet\n";
echo "• examples-curl.sh - Exemples curl pratiques\n\n";

echo "✨ Plugin Prêt pour la Production !\n";
echo "Compatible WordPress 6.0+ et PHP 8.0+\n\n";
?>