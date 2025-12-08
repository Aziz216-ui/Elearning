# Script PowerShell pour tester le webhook Symfony
# Ce script crée un webhook et teste l'envoi

# Configuration
$baseUrl = "http://localhost:8000"
$webhookSiteUrl = "https://webhook.site/401957a8-71fe-4435-9776-8dfa554dbb5c"

# Lire le token du fichier
if (Test-Path ".webhook_token") {
    $token = (Get-Content ".webhook_token").Trim()
} else {
    Write-Host "❌ Token non trouvé! Lancez d'abord: php setup_webhook_admin.php" -ForegroundColor Red
    exit 1
}

Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║     Test du Système de Webhook - Symfony E-Learning          ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Étape 1: Créer un webhook
Write-Host "📝 ÉTAPE 1: Création du webhook" -ForegroundColor Yellow
Write-Host "URL webhook.site: $webhookSiteUrl" -ForegroundColor Green
Write-Host ""

$createWebhookBody = @{
    url = $webhookSiteUrl
    eventType = "all"
} | ConvertTo-Json

Write-Host "Envoi de la requête POST..." -ForegroundColor Gray

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/webhooks" `
        -Method POST `
        -Headers @{
            "Authorization" = "Bearer $token"
            "Content-Type" = "application/json"
        } `
        -Body $createWebhookBody `
        -UseBasicParsing
    
    $responseData = $response.Content | ConvertFrom-Json
    
    if ($responseData.success) {
        Write-Host "✅ Webhook créé avec succès!" -ForegroundColor Green
        Write-Host "ID: $($responseData.data.id)" -ForegroundColor Cyan
        Write-Host "Secret: $($responseData.data.secret)" -ForegroundColor Cyan
        Write-Host ""
        
        $webhookId = $responseData.data.id
        $secret = $responseData.data.secret
    }
    else {
        Write-Host "❌ Erreur: $($responseData.message)" -ForegroundColor Red
        exit 1
    }
}
catch {
    Write-Host "❌ Erreur de connexion: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Assurez-vous que:" -ForegroundColor Yellow
    Write-Host "  1. Symfony est en cours d'exécution sur http://localhost:8000" -ForegroundColor Yellow
    Write-Host "  2. Le token est valide" -ForegroundColor Yellow
    Write-Host "  3. L'utilisateur a le rôle ROLE_ADMIN" -ForegroundColor Yellow
    exit 1
}

# Étape 2: Lister les webhooks
Write-Host "📋 ÉTAPE 2: Vérification de la liste des webhooks" -ForegroundColor Yellow
Write-Host ""

try {
    $listResponse = Invoke-WebRequest -Uri "$baseUrl/api/webhooks" `
        -Method GET `
        -Headers @{
            "Authorization" = "Bearer $token"
        } `
        -UseBasicParsing
    
    $listData = $listResponse.Content | ConvertFrom-Json
    
    if ($listData.success) {
        Write-Host "✅ Webhooks actuels:" -ForegroundColor Green
        foreach ($webhook in $listData.data) {
            Write-Host "  - ID: $($webhook.id) | URL: $($webhook.url) | Événement: $($webhook.eventType) | Actif: $($webhook.isActive)" -ForegroundColor Cyan
        }
        Write-Host ""
    }
}
catch {
    Write-Host "⚠️  Impossible de lister les webhooks: $($_.Exception.Message)" -ForegroundColor Yellow
}

# Étape 3: Tester le webhook
Write-Host "🧪 ÉTAPE 3: Test d'envoi du webhook" -ForegroundColor Yellow
Write-Host ""

try {
    $testResponse = Invoke-WebRequest -Uri "$baseUrl/api/webhooks/$webhookId/test" `
        -Method POST `
        -Headers @{
            "Authorization" = "Bearer $token"
            "Content-Type" = "application/json"
        } `
        -UseBasicParsing
    
    $testData = $testResponse.Content | ConvertFrom-Json
    
    if ($testData.success) {
        Write-Host "✅ Test envoyé avec succès!" -ForegroundColor Green
        Write-Host "Message: $($testData.message)" -ForegroundColor Cyan
        Write-Host ""
    }
    else {
        Write-Host "⚠️  Erreur lors du test: $($testData.message)" -ForegroundColor Yellow
    }
}
catch {
    Write-Host "❌ Erreur lors du test: $($_.Exception.Message)" -ForegroundColor Red
}

# Étape 4: Instructions finales
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║              ✅ CONFIGURATION TERMINÉE                        ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

Write-Host "📊 Résumé:" -ForegroundColor Cyan
Write-Host "  • Webhook ID: $webhookId" -ForegroundColor White
Write-Host "  • URL cible: $webhookSiteUrl" -ForegroundColor White
Write-Host "  • Secret HMAC: $secret" -ForegroundColor White
Write-Host "  • Événements: all (quiz_started + quiz_completed)" -ForegroundColor White
Write-Host ""

Write-Host "🔍 Vérification:" -ForegroundColor Yellow
Write-Host "  1. Allez sur: $webhookSiteUrl" -ForegroundColor Cyan
Write-Host "  2. Vous devriez voir des requêtes webhook reçues" -ForegroundColor Cyan
Write-Host "  3. Regardez les en-têtes X-Webhook-Signature" -ForegroundColor Cyan
Write-Host ""

Write-Host "🎯 Prochaines étapes:" -ForegroundColor Yellow
Write-Host "  1. Connectez-vous en tant qu'étudiant" -ForegroundColor Cyan
Write-Host "  2. Cliquez sur 'Commencer Quiz'" -ForegroundColor Cyan
Write-Host "  3. Retournez sur webhook.site pour voir la notification" -ForegroundColor Cyan
Write-Host "  4. Complétez le quiz et voyez 'quiz_completed'" -ForegroundColor Cyan
Write-Host ""

Write-Host "💾 Sauvegardez ces informations:" -ForegroundColor Magenta
Write-Host "  Token: $token" -ForegroundColor White
Write-Host "  Webhook ID: $webhookId" -ForegroundColor White
Write-Host "  Secret: $secret" -ForegroundColor White
Write-Host ""
