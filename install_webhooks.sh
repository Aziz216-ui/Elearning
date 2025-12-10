#!/bin/bash

# Script d'installation du système de webhooks Elearning
# Usage: bash install_webhooks.sh

set -e

echo "╔════════════════════════════════════════╗"
echo "║   Installation Webhooks - Elearning    ║"
echo "╚════════════════════════════════════════╝"
echo ""

# Vérifier que Symfony est installé
if [ ! -f "bin/console" ]; then
    echo "❌ Erreur: Ce script doit être exécuté depuis la racine du projet Symfony"
    exit 1
fi

echo "📦 Installation des dépendances..."
composer install

echo ""
echo "🗄️  Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo ""
echo "✅ Migrations appliquées avec succès"

echo ""
echo "📝 Configuration des variables d'environnement..."

# Vérifier si .env existe
if [ ! -f ".env" ]; then
    echo "⚠️  .env non trouvé. Veuillez copier .env.webhook à .env"
    cp .env.webhook .env.local 2>/dev/null || echo "Copie échouée"
else
    echo "✓ .env existe déjà"
fi

echo ""
echo "🧪 Test du système..."

# Créer un webhook de test
echo "Création d'un webhook de test..."
php bin/console doctrine:query:sql "INSERT INTO webhook_subscription (admin_id, url, event_type, is_active, created_at, secret) 
VALUES (1, 'https://webhook.site/test', 'quiz_started', 1, NOW(), SHA2(RAND(), 256)) 
ON DUPLICATE KEY UPDATE updated_at=NOW();"

echo ""
echo "✅ Installation terminée!"
echo ""
echo "📋 Prochaines étapes:"
echo "1. Allez à http://localhost:8000/admin/webhooks"
echo "2. Créez votre premier webhook"
echo "3. Testez-le en cliquant le bouton 🧪"
echo ""
echo "📚 Documentation: Consultez WEBHOOK_DOCUMENTATION.md"
echo ""
