<?php
/**
 * Script pour créer un webhook de test et vérifier les notifications
 */

// URL de l'interface admin
$adminUrl = "http://127.0.0.1:8000/admin/webhooks";

echo "=== Configuration Webhook pour Notifications Quiz ===\n\n";
echo "1. Serveur web démarré sur: http://127.0.0.1:8000\n";
echo "2. Interface admin webzhook: j:ares: $admin academia\n\n";

echo "YCÉDURES:\n";
echo "1. Connectez-vous en tant qu'admin sur: http://127.0.0.1:8000/login\n";
echo "2. Allez à: $adminUrl\n";
echo "3. Cliquez sur 'Créer un nouveau webhook'\n";
echo "4. Remplissez le formulaire:\n";
echo "   - URL: https://webhook.site/your-unique-url (obtenez depuis webhook.site)\n";
echo "   - Type d'événement: quiz_started\n";
echo "5. Cliquez sur 'Tester' pour vérifier l'envoi\n";
echo "6. Commencez un quiz pour tester la notification réelle\n\n";

echo "URLS UTILES:\n";
echo "- Dashboard webhooks: $adminUrl\n";
echo "- Statut webhooks: http://127.0.0.1:8000/admin/webhooks/status\n";
echo "- Créer webhook: http://127.0.0.1:8000/admin/webhooks/new\n\n";

echo "POUR TESTER RAPIDEMENT:\n";
echo "1. Allez sur webhook.site pour obtenir une URL de test\n";
echo "2. Créez le webhook avec cette URL\n";
echo "3. Testez depuis l'interface admin\n";
echo "4. Démarrez un quiz en tant qu'étudiant\n\n";

echo "Si vous avez besoin d'un script de test automatique, exécutez:\n";
echo "php setup_webhook_test.php\n";
?>
