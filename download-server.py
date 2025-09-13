#!/usr/bin/env python3
"""
Simple HTTP server for downloading the Mr.WordPress Tools plugin ZIP file
"""

import http.server
import socketserver
import os
import mimetypes

class DownloadHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/':
            # Serve download page
            self.send_response(200)
            self.send_header('Content-type', 'text/html')
            self.end_headers()
            
            html_content = '''<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mr.WordPress Tools v1.0.0 - Téléchargement</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            text-align: center;
        }
        h1 { color: #333; margin-bottom: 1rem; }
        .plugin-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            text-align: left;
        }
        .download-btn {
            background: #0073aa;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            text-decoration: none;
            display: inline-block;
            margin: 1rem 0;
            transition: background 0.3s;
        }
        .download-btn:hover { background: #005a87; }
        .features {
            text-align: left;
            margin: 1.5rem 0;
        }
        .features li {
            margin: 0.5rem 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Mr.WordPress Tools v1.0.0</h1>
        <p><strong>Plugin WordPress Professionnel - Agent de Gestion à Distance</strong></p>
        
        <div class="plugin-info">
            <h3>📦 Informations du Package</h3>
            <ul>
                <li><strong>Version:</strong> 1.0.0</li>
                <li><strong>Taille:</strong> 111.7 KB</li>
                <li><strong>Fichiers:</strong> 20 fichiers inclus</li>
                <li><strong>Compatibilité:</strong> WordPress 6.0+ et PHP 8.0+</li>
            </ul>
        </div>
        
        <div class="features">
            <h3>✨ Fonctionnalités Principales</h3>
            <ul>
                <li>🔒 <strong>API REST sécurisée</strong> avec authentification HMAC-SHA256</li>
                <li>🛠️ <strong>Mode maintenance</strong> avec système de bypass par cookie</li>
                <li>🐛 <strong>Mode debug</strong> configurable (profil simple)</li>
                <li>📊 <strong>Comptage des mises à jour</strong> (core, plugins, thèmes)</li>
                <li>📧 <strong>Système d'email</strong> pour les notifications</li>
                <li>⚙️ <strong>Interface d'administration</strong> minimaliste</li>
                <li>📚 <strong>Documentation complète</strong> avec exemples</li>
                <li>🧪 <strong>Scripts de test</strong> (PHP + curl)</li>
            </ul>
        </div>
        
        <a href="/download" class="download-btn">
            📥 Télécharger mrwp-tools-v1.0.0.zip
        </a>
        
        <div class="warning">
            <strong>🔧 Instructions d'installation :</strong><br>
            1. Téléchargez le fichier ZIP<br>
            2. WordPress Admin → Extensions → Ajouter une extension<br>
            3. Téléverser le fichier ZIP et activer<br>
            4. Configurer dans Réglages → Mr.WordPress Tools
        </div>
        
        <p><small>Plugin développé par <strong>Mr.WordPress</strong> - Compatible production</small></p>
    </div>
</body>
</html>'''
            
            self.wfile.write(html_content.encode('utf-8'))
            
        elif self.path == '/download':
            # Serve the ZIP file
            zip_file = 'mrwp-tools-v1.0.0.zip'
            if os.path.exists(zip_file):
                self.send_response(200)
                self.send_header('Content-Type', 'application/zip')
                self.send_header('Content-Disposition', 'attachment; filename="mrwp-tools-v1.0.0.zip"')
                self.send_header('Content-Length', str(os.path.getsize(zip_file)))
                self.end_headers()
                
                with open(zip_file, 'rb') as f:
                    self.wfile.write(f.read())
            else:
                self.send_error(404, "ZIP file not found")
        else:
            super().do_GET()

if __name__ == "__main__":
    PORT = 3000
    
    print(f"🚀 Mr.WordPress Tools v1.0.0 - Serveur de téléchargement")
    print(f"📡 Démarrage du serveur sur le port {PORT}...")
    print(f"📦 Fichier ZIP prêt: mrwp-tools-v1.0.0.zip")
    
    # Vérifier que le fichier ZIP existe
    if os.path.exists('mrwp-tools-v1.0.0.zip'):
        file_size = os.path.getsize('mrwp-tools-v1.0.0.zip')
        print(f"✅ ZIP trouvé - Taille: {file_size:,} bytes ({file_size/1024:.1f} KB)")
    else:
        print("❌ Erreur: Fichier ZIP non trouvé!")
        exit(1)
    
    with socketserver.TCPServer(("", PORT), DownloadHandler) as httpd:
        print(f"🌐 Serveur actif sur http://localhost:{PORT}")
        print(f"📥 Accédez à cette URL pour télécharger le plugin")
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\n🛑 Serveur arrêté")