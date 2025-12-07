# 🎓 Système de Notification Webhook pour Quizzes

## Vue d'ensemble

Un système complet et en temps réel qui notifie instantanément les administrateurs lorsqu'un étudiant **commence** ou **complète** un quiz, en utilisant des **Webhooks API**.

### 🎯 Fonctionnalités principales

✅ **Session Quiz Automatique** - Création automatique d'une session lors du clic "Commencer Quiz"  
✅ **Notification Webhook** - Envoi instantané de notifications aux administrateurs  
✅ **HMAC Signature** - Sécurité avec signatures HMAC-SHA256  
✅ **Gestion des Webhooks** - API complète CRUD pour gérer les webhooks  
✅ **Retry Automatique** - Tentatives automatiques en cas d'échec  
✅ **Monitoring** - Suivi des tentatives d'envoi et des erreurs  
✅ **Événements Multiples** - Support pour quiz_started, quiz_completed, et all

---

## 📋 Architecture

### Entités

#### **QuizResult** (Modifié)
- **startedAt** : `DateTimeImmutable` - Enregistre quand l'étudiant a commencé
- Les autres colonnes existantes conservées

#### **WebhookSubscription** (Nouvelle)
```php
- id: int (PK)
- admin_id: int (FK) → User
- url: string(500) - L'URL du webhook
- eventType: string(50) - quiz_started | quiz_completed | all
- isActive: boolean - Webhook activé/désactivé
- createdAt: DateTimeImmutable
- lastTriggeredAt: DateTimeImmutable (nullable)
- failureCount: int - Nombre d'échecs consécutifs
- secret: string(255) - Secret HMAC pour la signature
```

### Services

#### **QuizNotificationService**
Gère la construction et l'envoi des notifications:
- `notifyQuizStarted(QuizResult)` - Envoie une notification de démarrage
- `notifyQuizCompleted(QuizResult)` - Envoie une notification de complétion
- Construit les payloads avec toutes les informations pertinentes

#### **WebhookDispatcher**
Gère l'envoi HTTP des webhooks:
- `dispatch(WebhookSubscription, payload)` - Envoie un webhook
- Retry automatique jusqu'à 3 fois
- HMAC-SHA256 signature
- Désactive les webhooks après 10 échecs consécutifs

### Repositories

#### **WebhookSubscriptionRepository**
- `findActiveWebhooksForEvent(string)` - Récupère les webhooks actifs pour un type d'événement
- `findByAdmin(User)` - Récupère les webhooks d'un admin
- `findActiveByAdmin(User)` - Récupère les webhooks actifs d'un admin
- `findFailedWebhooks(int)` - Récupère les webhooks avec trop d'erreurs

---

## 🔌 API Endpoints

### Base URL
```
POST /api/webhooks
GET /api/webhooks
GET /api/webhooks/{id}
PUT /api/webhooks/{id}
DELETE /api/webhooks/{id}
POST /api/webhooks/{id}/test
```

### Endpoints Détaillés

#### 1. **Lister les webhooks**
```http
GET /api/webhooks
Content-Type: application/json
Authorization: Bearer {token}
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
      "lastTriggeredAt": "2025-12-06T22:30:00+00:00",
      "failureCount": 0
    }
  ]
}
```

#### 2. **Créer un webhook**
```http
POST /api/webhooks
Content-Type: application/json
Authorization: Bearer {token}

{
  "url": "https://your-domain.com/webhook/endpoint",
  "eventType": "quiz_started"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Webhook created successfully",
  "data": {
    "id": 1,
    "url": "https://your-domain.com/webhook/endpoint",
    "eventType": "quiz_started",
    "secret": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
  }
}
```

#### 3. **Consulter un webhook**
```http
GET /api/webhooks/{id}
Authorization: Bearer {token}
```

#### 4. **Mettre à jour un webhook**
```http
PUT /api/webhooks/{id}
Content-Type: application/json
Authorization: Bearer {token}

{
  "url": "https://new-url.com/webhook",
  "eventType": "quiz_completed",
  "isActive": true
}
```

#### 5. **Supprimer un webhook**
```http
DELETE /api/webhooks/{id}
Authorization: Bearer {token}
```

#### 6. **Tester un webhook**
```http
POST /api/webhooks/{id}/test
Authorization: Bearer {token}
```

---

## 📦 Format des Webhooks Reçus

### Événement: quiz_started

```json
{
  "event": "quiz_started",
  "timestamp": "2025-12-06T22:30:00+00:00",
  "data": {
    "quiz_result_id": 123,
    "student": {
      "id": 45,
      "email": "student@example.com",
      "fullName": "John Doe"
    },
    "quiz": {
      "id": 12,
      "title": "Mathematics Basics",
      "description": "Test your math knowledge",
      "totalPoints": 100,
      "timeLimit": 1800
    },
    "course": {
      "id": 5,
      "title": "Advanced Math"
    },
    "startedAt": "2025-12-06T22:30:00+00:00"
  }
}
```

### Événement: quiz_completed

```json
{
  "event": "quiz_completed",
  "timestamp": "2025-12-06T22:45:00+00:00",
  "data": {
    "quiz_result_id": 123,
    "student": {
      "id": 45,
      "email": "student@example.com",
      "fullName": "John Doe"
    },
    "quiz": {
      "id": 12,
      "title": "Mathematics Basics",
      "totalPoints": 100
    },
    "course": {
      "id": 5,
      "title": "Advanced Math"
    },
    "result": {
      "score": 85,
      "passed": true,
      "startedAt": "2025-12-06T22:30:00+00:00",
      "completedAt": "2025-12-06T22:45:00+00:00"
    }
  }
}
```

---

## 🔐 Sécurité - Signature HMAC

Chaque webhook est signé avec HMAC-SHA256 pour garantir l'authenticité.

### En-têtes de la requête

```
X-Webhook-Signature: sha256=abc123def456...
X-Webhook-Event: quiz_started
Content-Type: application/json
```

### Vérification de la signature (Node.js)

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
  const hash = crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(payload))
    .digest('hex');
  
  const expectedSignature = 'sha256=' + hash;
  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expectedSignature)
  );
}
```

### Vérification de la signature (Python)

```python
import hmac
import hashlib

def verify_webhook_signature(payload, signature, secret):
    expected_sig = 'sha256=' + hmac.new(
        secret.encode(),
        payload.encode(),
        hashlib.sha256
    ).hexdigest()
    
    return hmac.compare_digest(signature, expected_sig)
```

---

## 🚀 Flux d'exécution complet

### 1. Étudiant clique sur "Commencer Quiz"

```
Student Click
    ↓
QuizController::start()
    ↓
Check Permissions
    ↓
Create/Get QuizResult with startedAt
    ↓
Save to Database
    ↓
Call QuizNotificationService::notifyQuizStarted()
    ↓
Get Active Webhooks for "quiz_started"
    ↓
For Each Webhook:
    - Build payload
    - Generate HMAC signature
    - Call WebhookDispatcher::dispatch()
    ↓
Send POST request with retry logic
    ↓
Update failureCount or mark as failed
    ↓
Render Quiz Page to Student
```

### 2. Étudiant soumet les réponses

```
Submit Form
    ↓
QuizController::submit()
    ↓
Calculate Score
    ↓
Update QuizResult with score, passed, completedAt
    ↓
Save to Database
    ↓
Call QuizNotificationService::notifyQuizCompleted()
    ↓
Get Active Webhooks for "quiz_completed"
    ↓
Similar webhook dispatch process...
    ↓
Redirect to Results Page
```

---

## 📝 Configuration de base

### 1. Migration Doctrine
```bash
php bin/console doctrine:migrations:migrate
```

### 2. Créer un webhook (via API)

**Request:**
```bash
curl -X POST http://localhost:8000/api/webhooks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {YOUR_TOKEN}" \
  -d '{
    "url": "https://your-webhook-receiver.com/webhook",
    "eventType": "quiz_started"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Webhook created successfully",
  "data": {
    "id": 1,
    "url": "https://your-webhook-receiver.com/webhook",
    "eventType": "quiz_started",
    "secret": "webhook_secret_key_here"
  }
}
```

### 3. Implémentation côté Client

Voir les exemples dans `docs/webhook-client-examples/`

---

## 📚 Fichiers modifiés/créés

### Fichiers créés:
- `src/Entity/WebhookSubscription.php` - Entité Webhook
- `src/Service/QuizNotificationService.php` - Service de notification
- `src/Service/WebhookDispatcher.php` - Service d'envoi webhook
- `src/Controller/Api/WebhookApiController.php` - API webhooks
- `src/Repository/WebhookSubscriptionRepository.php` - Repository
- `migrations/Version20251206200000.php` - Migration Doctrine

### Fichiers modifiés:
- `src/Entity/QuizResult.php` - Ajout de startedAt
- `src/Controller/Front/QuizController.php` - Intégration notifications

---

## 🧪 Tests

### Tester via l'API

```bash
# Lister les webhooks
GET /api/webhooks

# Créer un webhook de test
POST /api/webhooks
{
  "url": "https://webhook.site/unique-id",
  "eventType": "quiz_started"
}

# Envoyer un webhook de test
POST /api/webhooks/1/test

# Consulter les détails
GET /api/webhooks/1

# Mettre à jour
PUT /api/webhooks/1
{
  "isActive": true
}

# Supprimer
DELETE /api/webhooks/1
```

### Service de test gratuit

Utilisez [webhook.site](https://webhook.site) pour tester les webhooks gratuitement.

---

## 🔍 Dépannage

### Erreur: "Access Denied"
- ✅ L'utilisateur doit avoir le rôle `ROLE_ADMIN`
- Vérifier dans la base de données: `SELECT roles FROM user WHERE id = ?`

### Webhooks non envoyés
- ✅ Vérifier que `isActive = true`
- ✅ Vérifier que l'URL est valide
- ✅ Vérifier les logs: `tail -f var/log/dev.log`

### Échecs d'envoi répétés
- Après 10 échecs, le webhook est automatiquement désactivé
- Réactiver via: `PUT /api/webhooks/{id}` avec `"isActive": true`

---

## 📋 Checklist d'implémentation

- [x] Migration Doctrine créée et exécutée
- [x] Entité WebhookSubscription créée
- [x] Services de notification créés
- [x] API webhooks fonctionnelle
- [x] QuizController intégré
- [x] Signatures HMAC implémentées
- [x] Retry logic implémentée
- [ ] Tests unitaires à ajouter
- [ ] Documentation admin UI à créer
- [ ] Logs à monitorer

---

## 📞 Support

Pour toute question ou problème, consultez:
1. Les logs: `var/log/`
2. Profiler Symfony: `http://localhost:8000/_profiler`
3. Les exemples dans `docs/`

---

**Version:** 1.0.0  
**Dernière mise à jour:** 2025-12-06  
**Auteur:** AI Assistant
