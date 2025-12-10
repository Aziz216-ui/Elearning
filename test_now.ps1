$baseUrl = "http://localhost:8000"
$webhookUrl = "https://webhook.site/401957a8-71fe-4435-9776-8dfa554dbb5c"
$token = (Get-Content ".webhook_token").Trim()

Write-Host "`n===== TEST WEBHOOK =====" -ForegroundColor Cyan
Write-Host "Creation du webhook..." -ForegroundColor Yellow

$payload = @{
    url = $webhookUrl
    eventType = "all"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/webhooks" `
        -Method POST `
        -Headers @{
            "Authorization" = "Bearer $token"
            "Content-Type" = "application/json"
        } `
        -Body $payload `
        -UseBasicParsing -ErrorAction Stop
    
    $data = $response.Content | ConvertFrom-Json
    
    if ($data.success) {
        $id = $data.data.id
        Write-Host "OK! Webhook ID: $id" -ForegroundColor Green
        Write-Host "Envoi du test..." -ForegroundColor Yellow
        
        $testResp = Invoke-WebRequest -Uri "$baseUrl/api/webhooks/$id/test" `
            -Method POST `
            -Headers @{"Authorization" = "Bearer $token"} `
            -UseBasicParsing -ErrorAction Stop
        
        Write-Host "OK! Test envoye" -ForegroundColor Green
        Write-Host "" -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Green
        Write-Host "WEBHOOK FONCTIONNE!" -ForegroundColor Green
        Write-Host "Verifiez: webhook.site/401957a8..." -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Green
    } else {
        Write-Host "Erreur: $($data.message)" -ForegroundColor Red
    }
}
catch {
    Write-Host "Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
