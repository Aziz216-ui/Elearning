# 📖 Guide d'Intégration Webhook Quiz

## 🎯 Objectif

Ce guide vous montre comment intégrer le système de webhooks de quiz dans votre application e-learning Symfony.

## ✅ Prérequis

- Symfony 6.x ou supérieur
- PHP 8.1+
- Doctrine ORM
- MySQL/PostgreSQL

## 📚 Étapes d'intégration

### Étape 1: Vérifier la migration

```bash
# Vérifier que la migration est exécutée
php bin/console doctrine:migrations:status

# Si nécessaire, exécuter la migration
php bin/console doctrine:migrations:migrate
```

**Tables créées:**
- `webhook_subscription` - Pour stocker les webhooks des admins
- Colonne `started_at` ajoutée à `quiz_result`

### Étape 2: Vérifier les services

Vos services sont automatiquement enregistrés dans Symfony:

```php
// Injecter les services dans vos contrôleurs
public function __construct(
    private QuizNotificationService $notificationService,
    private WebhookDispatcher $dispatcher,
) {}
```

### Étape 3: Vérifier le contrôleur QuizController

Le contrôleur a été modifié pour:

1. **Dans `start()` action:**
   - Créer une nouvelle `QuizResult` avec `startedAt`
   - Appeler `notificationService->notifyQuizStarted()`

2. **Dans `submit()` action:**
   - Mettre à jour la `QuizResult` existante
   - Appeler `notificationService->notifyQuizCompleted()`

### Étape 4: Tester l'API Webhooks

#### 4.1 Créer un compte admin (si nécessaire)

Assurez-vous d'avoir un utilisateur avec le rôle `ROLE_ADMIN`:

```php
// En base de données:
UPDATE user SET roles = '["ROLE_ADMIN"]' WHERE email = 'admin@example.com';
```

#### 4.2 Créer un webhook

```bash
curl -X POST http://localhost:8000/api/webhooks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -d '{
    "url": "https://webhook.site/your-unique-id",
    "eventType": "quiz_started"
  }'
```

Réponse:
```json
{
  "success": true,
  "message": "Webhook created successfully",
  "data": {
    "id": 1,
    "url": "https://webhook.site/your-unique-id",
    "eventType": "quiz_started",
    "secret": "abc123def456..."
  }
}
```

#### 4.3 Lister les webhooks

```bash
curl http://localhost:8000/api/webhooks \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN"
```

#### 4.4 Tester un webhook

```bash
curl -X POST http://localhost:8000/api/webhooks/1/test \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN"
```

### Étape 5: Recevoir les webhooks

Vous avez trois options pour recevoir les webhooks:

#### Option A: Utiliser webhook.site (pour tester)

1. Allez sur https://webhook.site
2. Copiez votre URL unique
3. Créez un webhook avec cette URL
4. Testez - vous verrez les requêtes arriver en temps réel

#### Option B: Implémenter un serveur Node.js

```bash
# Installer les dépendances
npm install express

# Créer le fichier webhook-receiver-nodejs.js
# (voir examples/webhook-receiver-nodejs.js)

# Démarrer le serveur
node webhook-receiver-nodejs.js
```

#### Option C: Implémenter un serveur Python

```bash
# Installer les dépendances
pip install flask

# Démarrer le serveur
python webhook-receiver-python.py
```

## 🔐 Sécurité

### Vérifier la signature

Chaque webhook contient un en-tête `X-Webhook-Signature` avec une signature HMAC-SHA256.

**Node.js:**
```javascript
const crypto = require('crypto');

const signature = req.headers['x-webhook-signature'];
const payload = JSON.stringify(req.body);
const secret = 'your-secret-key';

const expectedSig = 'sha256=' + crypto
  .createHmac('sha256', secret)
  .update(payload)
  .digest('hex');

const isValid = crypto.timingSafeEqual(
  Buffer.from(signature),
  Buffer.from(expectedSig)
);
```

**Python:**
```python
import hmac
import hashlib

signature = request.headers.get('X-Webhook-Signature')
payload = request.get_data()
secret = 'your-secret-key'

expected_sig = 'sha256=' + hmac.new(
    secret.encode(),
    payload,
    hashlib.sha256
).hexdigest()

is_valid = hmac.compare_digest(signature, expected_sig)
```

## 📊 Structure des événements

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
      "title": "Math Quiz",
      "description": "Test your math skills",
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
      "title": "Math Quiz",
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

## 🐛 Débogage

### Activer les logs

Modifiez `config/packages/monolog.yaml`:

```yaml
when@dev:
    monolog:
        handlers:
            main:
                type: rotating_file
                path: '%kernel.logs_dir%/webhook.log'
                level: debug
```

### Consulter les logs

```bash
tail -f var/log/webhook.log
```

### Vérifier les webhooks en base de données

```sql
-- Voir tous les webhooks
SELECT * FROM webhook_subscription;

-- Voir les webhooks actifs
SELECT * FROM webhook_subscription WHERE is_active = 1;

-- Voir les webhooks avec erreurs
SELECT * FROM webhook_subscription WHERE failure_count > 0;

-- Voir le dernier déclenchement
SELECT id, url, last_triggered_at, failure_count 
FROM webhook_subscription 
ORDER BY last_triggered_at DESC LIMIT 5;
```

### Réinitialiser un webhook

```sql
UPDATE webhook_subscription 
SET failure_count = 0, is_active = 1 
WHERE id = 1;
```

## 💡 Cas d'usage

### 1. Envoyer un email à l'admin

```javascript
// webhook-receiver-nodejs.js
function handleQuizStarted(data) {
  const { student, quiz } = data;
  
  // Envoyer un email
  emailService.send({
    to: 'admin@example.com',
    subject: `${student.fullName} a commencé: ${quiz.title}`,
    template: 'quiz-started',
    data: data
  });
}
```

### 2. Mettre à jour un dashboard

```javascript
// Envoyer via WebSocket pour les mises à jour en temps réel
io.emit('quiz:started', {
  student: data.student,
  quiz: data.quiz,
  timestamp: new Date()
});
```

### 3. Enregistrer les statistiques

```python
# webhook-receiver-python.py
def handleQuizCompleted(data):
    result = data.get('result')
    student = data.get('student')
    
    # Enregistrer en base de données
    db.insert('quiz_statistics', {
        'student_id': student['id'],
        'score': result['score'],
        'passed': result['passed'],
        'completed_at': result['completedAt']
    })
```

## ✨ Bonnes pratiques

1. **Vérifier toujours la signature** - Valider le header `X-Webhook-Signature`
2. **Répondre rapidement** - Renvoyer 200 OK immédiatement, puis traiter en arrière-plan
3. **Implémenter un retry** - Rejeu automatique des webhooks échoués
4. **Monitorer les erreurs** - Suivre les webhooks désactivés
5. **Utiliser des services** - Découpler la réception du traitement

## 📞 Support

Besoin d'aide?

- Vérifier les logs: `tail -f var/log/dev.log`
- Consulter le profiler Symfony: `http://localhost:8000/_profiler`
- Tester avec webhook.site: https://webhook.site
- Vérifier la configuration: `php bin/console config:dump-reference webhook`

---

**Version:** 1.0.0  
**Dernière mise à jour:** 2025-12-06
