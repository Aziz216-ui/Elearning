# 🎓 Système de Notifications Webhook - Elearning

## 📋 Vue d'ensemble

Ce système permet aux **administrateurs** d'être notifiés en **temps réel** lorsque des événements importants se produisent sur les quiz via des **Webhooks HTTPS**.

```
┌─────────────────┐
│ ÉTUDIANT        │
│ Clique: Commencer
│ Quiz            │
└────────┬────────┘
         │
         │ POST /quiz/start/{id}
         ▼
┌──────────────────────────────────┐
│ BACKEND SYMFONY                  │
│ ✓ Créer QuizResult               │
│ ✓ Set startedAt = NOW()          │
│ ✓ Notifier admins                │
└──────┬───────────────────────────┘
       │
       │ Déclencher webhook
       ▼
┌──────────────────────────────────┐
│ WEBHOOK DISPATCHER               │
│ ✓ Signer le payload (HMAC-SHA256)│
│ ✓ POST vers l'URL admin          │
│ ✓ Retry 3x en cas d'erreur       │
└──────┬───────────────────────────┘
       │
       │ HTTP POST + Signature
       ▼
┌──────────────────────────────────┐
│ SYSTÈME ADMIN (ex: Slack/Email)  │
│ ✓ Vérifier la signature          │
│ ✓ Traiter l'événement           │
│ ✓ Notifier l'admin               │
└──────────────────────────────────┘
```

## 🚀 Démarrage rapide

### 1️⃣ Installation

```bash
# Exécuter la migration
php bin/console doctrine:migrations:migrate

# Vous pouvez également générer une nouvelle migration si vous avez modifié les entités
php bin/console make:migration --name "Add quiz webhooks"
```

### 2️⃣ Accéder à l'interface Admin

```
URL: http://localhost:8000/admin/webhooks
Rôle requis: ROLE_ADMIN
```

### 3️⃣ Créer votre premier webhook

1. Allez à `/admin/webhooks`
2. Cliquez "Créer un nouveau webhook"
3. Entrez:
   - **URL**: `https://webhook.site/unique-id` (ou votre URL)
   - **Type d'événement**: `quiz_started`
4. Validez
5. Copiez la **clé secrète** générée

### 4️⃣ Tester le webhook

```bash
# Option 1: Via l'interface
Cliquez sur le bouton 🧪 "Tester"

# Option 2: Via l'API
curl -X POST http://localhost:8000/api/webhooks/1/test \
  -H "Authorization: Bearer YOUR_TOKEN"

# Option 3: Faire passer un quiz (génère un événement réel)
```

## 📊 Architecture

### Entités

| Entité | Description |
|--------|------------|
| `QuizResult` | Représente la participation d'un étudiant à un quiz. **Nouveau**: `startedAt` enregistre l'heure de début |
| `WebhookSubscription` | Configuration d'un webhook par un admin (URL, événement, clé secrète) |

### Services

| Service | Responsabilité |
|---------|-----------------|
| `QuizNotificationService` | Gère les notifications quiz (construction du payload) |
| `WebhookDispatcher` | Envoie les requêtes HTTP, gère les retries, signe les payloads |

### Controllers

| Endpoint | Méthode | Description |
|----------|---------|------------|
| `/api/webhooks` | GET | Lister les webhooks |
| `/api/webhooks` | POST | Créer un webhook |
| `/api/webhooks/{id}` | GET | Voir un webhook |
| `/api/webhooks/{id}` | PUT | Mettre à jour |
| `/api/webhooks/{id}` | DELETE | Supprimer |
| `/api/webhooks/{id}/test` | POST | Tester |
| `/admin/webhooks` | GET | Dashboard des webhooks |
| `/admin/webhooks/status` | GET | Statut détaillé |

## 📨 Format des événements

### Événement: `quiz_started`

**Quand**: L'étudiant clique sur "Commencer Quiz"

```json
{
  "event": "quiz_started",
  "timestamp": "2024-12-06T19:00:00+00:00",
  "data": {
    "quiz_result_id": 123,
    "student": {
      "id": 45,
      "email": "student@example.com",
      "fullName": "John Doe"
    },
    "quiz": {
      "id": 10,
      "title": "Mathematics Quiz",
      "description": "Basic math test",
      "totalPoints": 100,
      "timeLimit": 1800
    },
    "course": {
      "id": 5,
      "title": "Mathematics 101"
    },
    "startedAt": "2024-12-06T19:00:00+00:00"
  }
}
```

### Événement: `quiz_completed`

**Quand**: L'étudiant soumet ses réponses

```json
{
  "event": "quiz_completed",
  "timestamp": "2024-12-06T19:30:00+00:00",
  "data": {
    "quiz_result_id": 123,
    "student": {
      "id": 45,
      "email": "student@example.com",
      "fullName": "John Doe"
    },
    "quiz": {
      "id": 10,
      "title": "Mathematics Quiz",
      "totalPoints": 100
    },
    "course": {
      "id": 5,
      "title": "Mathematics 101"
    },
    "result": {
      "score": 85,
      "passed": true,
      "startedAt": "2024-12-06T19:00:00+00:00",
      "completedAt": "2024-12-06T19:30:00+00:00"
    }
  }
}
```

## 🔐 Sécurité

### Signature HMAC-SHA256

Chaque webhook inclut une **signature** qui permet de vérifier son authenticité.

**En-têtes de la requête**:
```
X-Webhook-Signature: sha256=e9c8a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1a0b9c8d7e6f5a4b3c2d1e0f
X-Webhook-Event: quiz_started
Content-Type: application/json
```

**Vérification en PHP**:
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'votre-clé-secrète'; // Récupérée lors de la création

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected, $signature)) {
    // ✅ Authentique
} else {
    // ❌ Rejeté
}
```

## 🧪 Exemples

### Exemple 1: Slack Notification

```bash
# Créer un webhook
URL: https://hooks.slack.com/services/YOUR/WEBHOOK/URL
Type: quiz_started

# L'admin reçoit une notification:
# 📚 John Doe a commencé Mathematics Quiz
#    Cours: Mathematics 101
#    Heure: 19:00
```

### Exemple 2: Dashboard en temps réel

```javascript
// Node.js receiver
app.post('/webhook/quiz', (req, res) => {
    const event = req.body.event;
    const data = req.body.data;
    
    // Émettre aux clients WebSocket connectés
    io.emit('quiz-event', { event, data });
    
    res.json({ success: true });
});
```

### Exemple 3: Email de notification

```php
// Envoyer un email à l'admin
$emailBody = sprintf(
    "L'étudiant %s a commencé le quiz '%s'",
    $data['student']['fullName'],
    $data['quiz']['title']
);

$mailer->send('admin@example.com', 'Quiz started', $emailBody);
```

## 📝 Fichiers modifiés

```
📁 src/
  📁 Entity/
    📄 QuizResult.php (+ startedAt)
    📄 WebhookSubscription.php (NEW)
  
  📁 Service/
    📄 QuizNotificationService.php (NEW)
    📄 WebhookDispatcher.php (NEW)
  
  📁 Controller/
    📁 Api/
      📄 WebhookApiController.php (NEW)
    📁 Admin/
      📄 WebhookAdminController.php (NEW)
    📁 Front/
      📄 QuizController.php (modifié: start & submit)
  
  📁 Repository/
    📄 WebhookSubscriptionRepository.php (NEW)

📁 templates/
  📁 admin/webhooks/
    📄 index.html.twig (NEW)
    📄 new.html.twig (NEW)
    📄 edit.html.twig (NEW)
    📄 status.html.twig (NEW)

📁 migrations/
  📄 Version20251206190000.php (NEW)

📁 examples/
  📄 webhook_receiver_example.php (NEW)
  📄 webhook_receiver_nodejs.js (NEW)
  📄 webhook_receiver_python.py (NEW)

📄 WEBHOOK_DOCUMENTATION.md (NEW)
📄 README.md (ce fichier)
```

## 🐛 Dépannage

### Le webhook ne s'envoie pas?

```bash
# 1. Vérifier que le webhook est actif
SELECT * FROM webhook_subscription WHERE id = 1;

# 2. Vérifier l'URL est valide
curl -X POST https://mon-url.com/webhook

# 3. Vérifier les logs
tail -f var/log/dev.log | grep -i webhook

# 4. Tester via l'interface
# Allez à /admin/webhooks/{id} et cliquez "Tester"
```

### La signature ne correspond pas?

```bash
# 1. Vérifier la clé secrète
# Aller à /admin/webhooks/{id} et copier la clé

# 2. Vérifier le payload n'a pas été modifié
# Utiliser le payload brut (pas parsé en JSON)

# 3. Utiliser hash_equals() pour comparer
if (!hash_equals($expected, $actual)) {
    // Attention: timing attack!
}
```

### Le webhook est désactivé?

```bash
# Après 10 échecs, le webhook est automatiquement désactivé
# Pour le réactiver:
# 1. Allez à /admin/webhooks/{id}/edit
# 2. Cochez "Actif"
# 3. Cliquez "Enregistrer"

# Vous pouvez aussi le faire via SQL:
UPDATE webhook_subscription SET is_active = 1, failure_count = 0 WHERE id = 1;
```

## 📊 Monitoring

### Voir le statut des webhooks

```bash
# Allez à /admin/webhooks/status

# Vous verrez:
# - Total des webhooks
# - Webhooks actifs
# - Webhooks avec problèmes
# - Webhooks désactivés
# - Détail de chaque webhook
```

### Logs

Les activités sont enregistrées dans `var/log/dev.log`:

```
[2024-12-06 19:00:15] app.INFO: Quiz started notification sent {"quiz_id":10,"user_id":45}
[2024-12-06 19:00:16] app.INFO: Webhook dispatched successfully {"webhook_id":1,"status":200}
[2024-12-06 19:00:20] app.ERROR: Webhook dispatch failed {"webhook_id":1,"retry":0}
```

## 🎯 Cas d'usage

| Cas d'usage | Description | Intégration |
|------------|------------|------------|
| **Slack Alert** | Notifier instantanément sur Slack | Slack Webhook |
| **Dashboard Real-time** | Afficher sur un dashboard en temps réel | WebSocket + Redis |
| **Email Notification** | Envoyer un email aux instructeurs | SMTP/SendGrid |
| **LMS Sync** | Synchroniser avec Moodle/Canvas | API REST |
| **Analytics** | Enregistrer pour analytics | Segment/Mixpanel |
| **Certificate Gen** | Générer un certificat automatiquement | Certificat Server |

## 📚 Références

- [Webhook Documentation Complète](./WEBHOOK_DOCUMENTATION.md)
- [Exemple PHP](./examples/webhook_receiver_example.php)
- [Exemple Node.js](./examples/webhook_receiver_nodejs.js)
- [Exemple Python](./examples/webhook_receiver_python.py)

## ❓ FAQ

**Q: Peut-on envoyer des webhooks à plusieurs URLs?**
R: Oui! Créer plusieurs webhooks avec des URLs différentes.

**Q: Que se passe-t-il si l'URL ne répond pas?**
R: Le système réessaye 3 fois avec délai, puis désactive après 10 échecs.

**Q: Comment sécuriser les webhooks?**
R: Vérifier la signature HMAC-SHA256 et valider l'URL.

**Q: Peut-on recevoir les deux événements?**
R: Oui! Créer un webhook avec `eventType: 'all'`.

**Q: Comment débuler les webhooks?**
R: Utiliser https://webhook.site ou ngrok + logs.

## 📞 Support

Pour des questions ou problèmes:
1. Vérifier la [Documentation Complète](./WEBHOOK_DOCUMENTATION.md)
2. Consulter les exemples dans `examples/`
3. Vérifier les logs dans `var/log/`
4. Tester via `/admin/webhooks/status`

---

**Version**: 1.0  
**Date**: 2024-12-06  
**Auteur**: Elearning System
