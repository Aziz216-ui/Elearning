#!/usr/bin/env pwsh

# ╔════════════════════════════════════════════════════════════════╗
# ║  Script de Test Webhook Symfony - Quick Start                ║
# ╚════════════════════════════════════════════════════════════════╝

param(
    [switch]$SkipServer = $false
)

$ErrorActionPreference = 'Stop'

# Configuration
$baseUrl = "http://localhost:8000"
$webhookUrl = "https://webhook.site/401957a8-71fe-4435-9776-8dfa554dbb5c"
$scriptPath = Get-Location

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║    Test du Système de Webhook - E-Learning Symfony          ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Étape 0: Vérifier le token
Write-Host "🔐 Vérification du token..." -ForegroundColor Yellow

if (-not (Test-Path ".webhook_token")) {
    Write-Host "❌ Token non trouvé!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Exécutez d'abord:" -ForegroundColor Yellow
    Write-Host "  php setup_webhook_admin.php" -ForegroundColor Cyan
    Write-Host ""
    exit 1
}

$token = (Get-Content ".webhook_token").Trim()
Write-Host "✅ Token trouvé: $($token.Substring(0, 16))..." -ForegroundColor Green
Write-Host ""

# Étape 1: Vérifier que Symfony est lancé
Write-Host "🔍 Vérification du serveur Symfony..." -ForegroundColor Yellow

try {
    $health = Invoke-WebRequest -Uri "$baseUrl/" -UseBasicParsing -ErrorAction Stop
    Write-Host "✅ Serveur Symfony actif sur $baseUrl" -ForegroundColor Green
}
catch {
    Write-Host "⚠️  Serveur Symfony non trouvé sur $baseUrl" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Lancez le serveur avec:" -ForegroundColor Cyan
    Write-Host "  cd '$scriptPath'" -ForegroundColor White
    Write-Host "  php -S localhost:8000 -t public" -ForegroundColor White
    Write-Host ""
    Write-Host "Puis réexécutez ce script avec:" -ForegroundColor Cyan
    Write-Host "  .\quick_test_webhook.ps1" -ForegroundColor White
    Write-Host ""
    exit 1
}
Write-Host ""

# Étape 2: Créer le webhook
Write-Host "📝 Étape 1: Création du webhook..." -ForegroundColor Yellow
Write-Host "   Cible: $webhookUrl" -ForegroundColor Gray
Write-Host "   Événement: all (quiz_started + quiz_completed)" -ForegroundColor Gray

$createBody = @{
    url = $webhookUrl
    eventType = "all"
} | ConvertTo-Json

try {
    $createResp = Invoke-WebRequest -Uri "$baseUrl/api/webhooks" `
        -Method POST `
        -Headers @{
            "Authorization" = "Bearer $token"
            "Content-Type" = "application/json"
        } `
        -Body $createBody `
        -UseBasicParsing
    
    $createData = $createResp.Content | ConvertFrom-Json
    
    if ($createData.success -ne $true) {
        Write-Host "❌ Erreur: $($createData.message)" -ForegroundColor Red
        exit 1
    }
    
    $webhookId = $createData.data.id
    $secret = $createData.data.secret
    
    Write-Host "✅ Webhook créé avec succès!" -ForegroundColor Green
    Write-Host "   ID: $webhookId" -ForegroundColor Cyan
    Write-Host "   Secret: $($secret.Substring(0, 16))..." -ForegroundColor Cyan
    Write-Host ""
}
catch {
    Write-Host "❌ Erreur lors de la création: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Étape 3: Lister les webhooks
Write-Host "📋 Étape 2: Vérification de la liste des webhooks..." -ForegroundColor Yellow

try {
    $listResp = Invoke-WebRequest -Uri "$baseUrl/api/webhooks" `
        -Method GET `
        -Headers @{
            "Authorization" = "Bearer $token"
        } `
        -UseBasicParsing
    
    $listData = $listResp.Content | ConvertFrom-Json
    
    if ($listData.success -ne $true) {
        Write-Host "⚠️  Erreur: $($listData.message)" -ForegroundColor Yellow
    }
    else {
        Write-Host "✅ Webhooks actuels:" -ForegroundColor Green
        foreach ($webhook in $listData.data) {
            Write-Host "   └─ ID: $($webhook.id) | $($webhook.eventType) | $($webhook.url)" -ForegroundColor Cyan
        }
    }
    Write-Host ""
}
catch {
    Write-Host "⚠️  Erreur: $($_.Exception.Message)" -ForegroundColor Yellow
    Write-Host ""
}

# Étape 4: Tester l'envoi
Write-Host "🧪 Étape 3: Test d'envoi du webhook..." -ForegroundColor Yellow
Write-Host "   Une requête webhook doit être envoyée à: $webhookUrl" -ForegroundColor Gray

try {
    $testResp = Invoke-WebRequest -Uri "$baseUrl/api/webhooks/$webhookId/test" `
        -Method POST `
        -Headers @{
            "Authorization" = "Bearer $token"
            "Content-Type" = "application/json"
        } `
        -UseBasicParsing
    
    $testData = $testResp.Content | ConvertFrom-Json
    
    if ($testData.success -ne $true) {
        Write-Host "⚠️  Erreur: $($testData.message)" -ForegroundColor Yellow
    }
    else {
        Write-Host "✅ Test envoyé avec succès!" -ForegroundColor Green
        Write-Host "   Message: $($testData.message)" -ForegroundColor Cyan
    }
    Write-Host ""
}
catch {
    Write-Host "❌ Erreur lors du test: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Résumé final
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║              ✅ TEST COMPLÉTÉ AVEC SUCCÈS!                    ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

Write-Host "📊 Résumé de la Configuration:" -ForegroundColor Cyan
Write-Host "   • Webhook ID: $webhookId" -ForegroundColor White
Write-Host "   • URL cible: $webhookUrl" -ForegroundColor White
Write-Host "   • Événements: quiz_started, quiz_completed" -ForegroundColor White
Write-Host "   • Statut: ✅ ACTIF" -ForegroundColor Green
Write-Host ""

Write-Host "🔍 Vérification:" -ForegroundColor Yellow
Write-Host "   1. Ouvrez: $webhookUrl" -ForegroundColor Cyan
Write-Host "   2. Vous devriez voir les requêtes webhook reçues" -ForegroundColor Cyan
Write-Host "   3. Regardez les en-têtes X-Webhook-Signature" -ForegroundColor Cyan
Write-Host ""

Write-Host "🎯 Prochaines Étapes:" -ForegroundColor Yellow
Write-Host "   1. Connectez-vous en tant qu'étudiant" -ForegroundColor Cyan
Write-Host "   2. Cliquez sur 'Commencer Quiz'" -ForegroundColor Cyan
Write-Host "   3. Retournez sur webhook.site pour voir la notification 'quiz_started'" -ForegroundColor Cyan
Write-Host "   4. Complétez le quiz et voyez 'quiz_completed'" -ForegroundColor Cyan
Write-Host ""

Write-Host "💾 Infos à Sauvegarder:" -ForegroundColor Magenta
Write-Host "   Webhook ID: $webhookId" -ForegroundColor White
Write-Host "   Secret: $secret" -ForegroundColor White
Write-Host ""

Write-Host "Pour envoyer un autre test:" -ForegroundColor Gray
Write-Host "   curl -X POST http://localhost:8000/api/webhooks/$webhookId/test -H \"Authorization: Bearer $token\"" -ForegroundColor White
Write-Host ""
