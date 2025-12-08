<?php
// Script pour configurer l'utilisateur admin et créer un token

require 'vendor/autoload.php';

// Charger les classes Symfony
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv('.env');

// Connexion à la base de données
$host = 'localhost';
$db = 'elearning';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║     Configuration Admin pour Webhooks - Symfony             ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    // Étape 1: Attribuer ROLE_ADMIN
    echo "📝 ÉTAPE 1: Attribution du rôle ROLE_ADMIN\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $stmt = $pdo->prepare("UPDATE user SET roles = :roles WHERE id = 1");
    $stmt->execute([':roles' => json_encode(['ROLE_ADMIN'])]);
    
    echo "✅ Rôle ROLE_ADMIN attribué avec succès!\n\n";
    
    // Étape 2: Afficher les infos de l'utilisateur
    echo "👤 ÉTAPE 2: Informations de l'utilisateur\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $stmt = $pdo->query("SELECT id, email, roles FROM user WHERE id = 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Rôles: {$user['roles']}\n\n";
    
    // Étape 3: Générer un token basique
    echo "🔑 ÉTAPE 3: Génération du token\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $token = bin2hex(random_bytes(32));
    
    // Sauvegarder le token en cache pour utilisation dans les requests
    file_put_contents('.webhook_token', $token);
    
    echo "✅ Token généré: $token\n";
    echo "   (Sauvegardé dans .webhook_token)\n\n";
    
    // Étape 4: Instructions
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║              ✅ CONFIGURATION COMPLÈTE                        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    echo "✨ Utilisation:\n";
    echo "   1. Lancez le serveur Symfony:\n";
    echo "      symfony serve\n\n";
    echo "   2. Utilisez ce token pour les appels API:\n";
    echo "      Authorization: Bearer $token\n\n";
    echo "   3. Ou lancez le script PowerShell:\n";
    echo "      .\\test_webhook.ps1\n\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>
