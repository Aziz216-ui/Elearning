@echo off
REM Script batch pour lancer Symfony et tester les webhooks

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║     Démarre du Serveur Symfony + Test Webhooks               ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

REM Vérifier que setup a été exécuté
if not exist ".webhook_token" (
    echo ❌ Token non trouvé!
    echo.
    echo Exécutez d'abord: php setup_webhook_admin.php
    echo.
    pause
    exit /b 1
)

REM Lancer Symfony en arrière-plan
echo 🚀 Lancement du serveur Symfony sur http://localhost:8000
echo.

start "Symfony Server" php -S localhost:8000 -t public

REM Attendre 3 secondes pour que le serveur démarre
timeout /t 3 /nobreak

REM Afficher le token
set /p token=<.webhook_token

echo.
echo ✅ Serveur Symfony lancé!
echo Token: %token%
echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║              🧪 TEST DU WEBHOOK                              ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

REM Créer le webhook
echo 📝 Étape 1: Création du webhook...
echo.

curl -X POST http://localhost:8000/api/webhooks ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Bearer %token%" ^
  -d "{\"url\": \"https://webhook.site/401957a8-71fe-4435-9776-8dfa554dbb5c\", \"eventType\": \"all\"}" ^
  -s | jq .

echo.
echo 📋 Étape 2: Vérification de la liste...
echo.

curl -X GET http://localhost:8000/api/webhooks ^
  -H "Authorization: Bearer %token%" ^
  -s | jq .

echo.
echo 🧪 Étape 3: Envoi d'un test...
echo.

curl -X POST http://localhost:8000/api/webhooks/1/test ^
  -H "Authorization: Bearer %token%" ^
  -s | jq .

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║              ✅ TEST COMPLÉTÉ                                 ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.
echo Vérifiez votre URL webhook.site:
echo https://webhook.site/401957a8-71fe-4435-9776-8dfa554dbb5c
echo.
echo Les requêtes webhook doivent apparaître avec:
echo   - Header: X-Webhook-Signature (signature HMAC-SHA256)
echo   - Body: Payload JSON avec les données du quiz
echo.
pause
