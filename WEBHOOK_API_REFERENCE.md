# 📍 API Webhooks - Référence rapide

## Base URL
```
http://localhost:8000/api/webhooks
```

## Authentication
Toutes les requêtes nécessitent l'en-tête `Authorization`:
```
Authorization: Bearer YOUR_AUTH_TOKEN
```

---

## 🔌 Endpoints

### 1. Lister les webhooks
```bash
GET /api/webhooks
```

**cURL:**
```bash
curl -X GET http://localhost:8000/api/webhooks \
  -H "Authorization: Bearer TOKEN"
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "url": "https://example.com/webhook",
      "eventType": "quiz_started",
      "isActive": true,
      "createdAt": "2025-12-06T22:26:39+00:00",
      "lastTriggeredAt": null,
      "failureCount": 0
    }
  ]
}
```

---

### 2. Créer un webhook
```bash
POST /api/webhooks
Content-Type: application/json
```

**cURL:**
```bash
curl -X POST http://localhost:8000/api/webhooks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "url": "https://your-domain.com/webhook",
    "eventType": "quiz_started"
  }'
```

**Body Parameters:**
| Param | Type | Description | Required |
|-------|------|-------------|----------|
| url | string(500) | URL du webhook | ✅ |
| eventType | string | quiz_started, quiz_completed, ou all | ✅ |

**Réponse:**
```json
{
  "success": true,
  "message": "Webhook created successfully",
  "data": {
    "id": 1,
    "url": "https://your-domain.com/webhook",
    "eventType": "quiz_started",
    "secret": "abc123def456ghij789klmn"
  }
}
```

⚠️ **Important:** Sauvegardez le `secret` - il est utilisé pour la signature HMAC!

---

### 3. Consulter un webhook
```bash
GET /api/webhooks/{id}
```

**cURL:**
```bash
curl -X GET http://localhost:8000/api/webhooks/1 \
  -H "Authorization: Bearer TOKEN"
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "url": "https://your-domain.com/webhook",
    "eventType": "quiz_started",
    "isActive": true,
    "createdAt": "2025-12-06T22:26:39+00:00",
    "lastTriggeredAt": "2025-12-06T22:35:00+00:00",
    "failureCount": 0,
    "secret": "abc123def456ghij789klmn"
  }
}
```

---

### 4. Mettre à jour un webhook
```bash
PUT /api/webhooks/{id}
Content-Type: application/json
```

**cURL:**
```bash
curl -X PUT http://localhost:8000/api/webhooks/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "url": "https://new-url.com/webhook",
    "eventType": "quiz_completed",
    "isActive": true
  }'
```

**Body Parameters (optionnels):**
| Param | Type | Description |
|-------|------|-------------|
| url | string(500) | Nouvelle URL |
| eventType | string | Nouveau type d'événement |
| isActive | boolean | Activer/désactiver |

**Réponse:**
```json
{
  "success": true,
  "message": "Webhook updated successfully",
  "data": {
    "id": 1,
    "url": "https://new-url.com/webhook",
    "eventType": "quiz_completed",
    "isActive": true
  }
}
```

---

### 5. Supprimer un webhook
```bash
DELETE /api/webhooks/{id}
```

**cURL:**
```bash
curl -X DELETE http://localhost:8000/api/webhooks/1 \
  -H "Authorization: Bearer TOKEN"
```

**Réponse:**
```json
{
  "success": true,
  "message": "Webhook deleted successfully"
}
```

---

### 6. Tester un webhook
```bash
POST /api/webhooks/{id}/test
```

**cURL:**
```bash
curl -X POST http://localhost:8000/api/webhooks/1/test \
  -H "Authorization: Bearer TOKEN"
```

**Réponse:**
```json
{
  "success": true,
  "message": "Test webhook sent successfully"
}
```

Cet endpoint envoie un webhook de test immédiatement pour vérifier votre configuration.

---

## 📊 Codes de statut HTTP

| Code | Signification |
|------|--------------|
| 200 | Succès |
| 201 | Créé (POST) |
| 400 | Requête invalide |
| 401 | Non authentifié |
| 403 | Accès refusé (manque ROLE_ADMIN) |
| 404 | Webhook introuvable |
| 500 | Erreur serveur |

---

## 🔐 En-têtes reçus sur le webhook

Quand votre serveur reçoit un webhook:

```
POST /webhook/quiz HTTP/1.1
Host: your-domain.com
Content-Type: application/json
X-Webhook-Signature: sha256=abc123def456...
X-Webhook-Event: quiz_started
```

**À vérifier:**
1. ✅ Header `X-Webhook-Signature` valide
2. ✅ Header `X-Webhook-Event` = quiz_started ou quiz_completed
3. ✅ Body JSON valide

---

## 💡 Cas d'usage

### Scénario 1: Suivre les quiz en temps réel
```javascript
// Créer un webhook pour quiz_started
POST /api/webhooks
{
  "url": "https://dashboard.example.com/api/quiz-notifications",
  "eventType": "quiz_started"
}

// Votre dashboard reçoit les notifications en temps réel
// et affiche un message "Student X a commencé Quiz Y"
```

### Scénario 2: Envoyer des emails
```javascript
// Créer un webhook pour tous les événements
POST /api/webhooks
{
  "url": "https://mail-service.example.com/webhook",
  "eventType": "all"
}

// Le service mail reçoit les notifications
// et envoie des emails aux administrateurs
```

### Scénario 3: Mettre à jour les statistiques
```javascript
// Créer un webhook pour les complétions
POST /api/webhooks
{
  "url": "https://analytics.example.com/webhook",
  "eventType": "quiz_completed"
}

// Votre service d'analytics reçoit les données
// et met à jour les statistiques en temps réel
```

---

## 🧪 Tests

### Test basique avec webhook.site

```bash
# 1. Allez sur https://webhook.site
# 2. Copiez votre URL (ex: https://webhook.site/abc123def456)
# 3. Créez un webhook:

curl -X POST http://localhost:8000/api/webhooks \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://webhook.site/abc123def456",
    "eventType": "quiz_started"
  }'

# 4. Testez le webhook:

curl -X POST http://localhost:8000/api/webhooks/1/test

# 5. Voir la requête sur webhook.site ✅
```

### Test avec Node.js

```bash
# 1. Clonez ou créez le serveur de test
# 2. Démarrez le serveur

node examples/webhook-receiver-nodejs.js

# 3. Créez un webhook pointant vers votre serveur

curl -X POST http://localhost:8000/api/webhooks \
  -H "Content-Type: application/json" \
  -d '{
    "url": "http://localhost:3000/webhook/quiz",
    "eventType": "quiz_started"
  }'

# 4. Testez

curl -X POST http://localhost:8000/api/webhooks/1/test

# 5. Voir les logs dans le terminal Node.js ✅
```

---

## 🐛 Débogage

### Vérifier que le webhook existe
```bash
curl http://localhost:8000/api/webhooks/1
```

### Vérifier les erreurs
```bash
curl http://localhost:8000/api/webhooks/1 | grep failureCount
```

### Réactiver un webhook échoué
```bash
curl -X PUT http://localhost:8000/api/webhooks/1 \
  -H "Content-Type: application/json" \
  -d '{
    "isActive": true
  }'
```

### Voir les logs
```bash
tail -f var/log/dev.log | grep webhook
```

---

## ✨ Bonnes pratiques

1. **Toujours vérifier la signature** HMAC
2. **Répondre rapidement** avec 200 OK
3. **Traiter en arrière-plan** les opérations longues
4. **Implémenter un retry** pour les erreurs
5. **Monitorer les webhooks** échoués
6. **Utiliser HTTPS** en production

---

## 🔗 Ressources

- 📖 [Documentation complète](./WEBHOOK_SYSTEM.md)
- 📋 [Guide d'intégration](./WEBHOOK_INTEGRATION_GUIDE.md)
- 💾 [Requêtes SQL](./docs/WEBHOOK_SQL_QUERIES.sql)
- 💻 [Exemples Node.js](./examples/webhook-receiver-nodejs.js)
- 🐍 [Exemples Python](./examples/webhook-receiver-python.py)
- 🐘 [Exemples PHP](./examples/webhook-receiver-php.php)

---

## 📞 Support

Besoin d'aide?
1. Vérifiez les logs: `tail -f var/log/dev.log`
2. Consultez la documentation: [WEBHOOK_SYSTEM.md](./WEBHOOK_SYSTEM.md)
3. Testez avec webhook.site: https://webhook.site

---

**Version:** 1.0.0  
**Dernière mise à jour:** 6 décembre 2025
