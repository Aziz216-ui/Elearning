@echo off
REM Script cURL pour tester les webhooks sans PowerShell
REM Utilisation: test_webhook.bat

setlocal enabledelayedexpansion

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║     Test Webhook API - cURL Simple                           ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

REM Lire le token
if exist ".webhook_token" (
    for /f "tokens=*" %%i in (.webhook_token) do set "TOKEN=%%i"
) else (
    echo ❌ Token non trouvé!
    echo.
    echo Exécutez d'abord: php setup_webhook_admin.php
    echo.
    pause
    exit /b 1
)

set "BASE_URL=http://localhost:8000"
set "WEBHOOK_URL=https://webhook.site/401957a8-71fe-4435-9776-8dfa554dbb5c"

echo Token: !TOKEN!
echo.

REM Test 1: GET /api/webhooks
echo 📋 GET /api/webhooks
echo ───────────────────────────────────────────────────────────────
curl -X GET !BASE_URL!/api/webhooks ^
  -H "Authorization: Bearer !TOKEN!" ^
  -H "Content-Type: application/json"
echo.
echo.

REM Test 2: POST /api/webhooks (create)
echo 📝 POST /api/webhooks
echo ───────────────────────────────────────────────────────────────
curl -X POST !BASE_URL!/api/webhooks ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Bearer !TOKEN!" ^
  -d "{\"url\": \"!WEBHOOK_URL!\", \"eventType\": \"all\"}"
echo.
echo.

REM Test 3: POST /api/webhooks/1/test
echo 🧪 POST /api/webhooks/1/test
echo ───────────────────────────────────────────────────────────────
curl -X POST !BASE_URL!/api/webhooks/1/test ^
  -H "Authorization: Bearer !TOKEN!" ^
  -H "Content-Type: application/json"
echo.
echo.

echo ✅ Tests complétés!
echo Vérifiez: !WEBHOOK_URL!
echo.
pause
