# Système de Notification Webhook pour Événements Quiz

## Overview

Ce système permet aux administrateurs de recevoir des notifications en temps réel via Webhooks lorsque des événements liés aux quiz se produisent (démarrage ou fin d'un quiz).

## Fonctionnalités principales

### 1. **Démarrage de session Quiz**
Quand un étudiant clique sur "Commencer Quiz":
- Une session `QuizResult` est créée dans la base de données avec un timestamp `started_at`
- Un webhook est déclenché pour notifier les administrateurs
- L'étudiant peut continuer normalement

### 2. **Notification de complétion**
Quand un étudiant soumet les réponses:
- La session est mise à jour avec le score et la date de complétion
- Un webhook `quiz_completed` est déclenché

### 3. **Gestion des Webhooks**
Les administrateurs peuvent:
- Créer plusieurs webhooks pour différentes URLs
- Choisir le type d'événement (quiz_started, quiz_completed, ou tous)
- Activer/désactiver les webhooks
- Tester les webhooks
- Voir le statut et l'historique des webhooks

### 4. **Sécurité**
- Chaque webhook reçoit une clé secrète unique
- Les payloads sont signés avec HMAC-SHA256
- Signature incluse dans l'en-tête `X-Webhook-Signature`

## Architecture

### Entités

#### QuizResult (modifiée)
```php
- id: int
- user: User
- quiz: Quiz
- score: float
- completedAt: DateTimeImmutable (nullable)
- startedAt: DateTimeImmutable (NEW) ← Enregistre quand le quiz a été commencé
- totalPoints: int
- passed: bool
- certificatePath: string (nullable)
```

#### WebhookSubscription (nouvelle)
```php
- id: int
- admin: User (ROLE_ADMIN)
- url: string (l'URL cible)
- eventType: string (quiz_started|quiz_completed|all)
- isActive: bool
- createdAt: DateTimeImmutable
- lastTriggeredAt: DateTimeImmutable (nullable)
- failureCount: int (pour tracker les erreurs)
- secret: string (clé secrète unique)
```

### Services

#### WebhookDispatcher
Responsable d'envoyer les webhooks avec:
- Gestion des retries (3 tentatives avec délai)
- Signature HMAC-SHA256
- Logging détaillé
- Désactivation automatique après 10 échecs

#### QuizNotificationService
Gère les notifications spécifiques aux quiz:
- `notifyQuizStarted(QuizResult)` - Envoie notification de démarrage
- `notifyQuizCompleted(QuizResult)` - Envoie notification de complétion
- Construction des payloads

### Controllers

#### Api/WebhookApiController (REST API)
- `GET /api/webhooks` - Lister les webhooks
- `POST /api/webhooks` - Créer un webhook
- `GET /api/webhooks/{id}` - Voir un webhook
- `PUT /api/webhooks/{id}` - Modifier un webhook
- `DELETE /api/webhooks/{id}` - Supprimer un webhook
- `POST /api/webhooks/{id}/test` - Tester un webhook

#### Admin/WebhookAdminController (Interface Web)
- `GET /admin/webhooks` - Tableau de bord des webhooks
- `GET /admin/webhooks/new` - Créer un nouveau webhook
- `GET /admin/webhooks/{id}/edit` - Modifier un webhook
- `POST /admin/webhooks/{id}/delete` - Supprimer un webhook
- `POST /admin/webhooks/{id}/test` - Tester un webhook
- `GET /admin/webhooks/status` - Voir le statut détaillé

## Format des Webhooks

### Payload: Quiz Started

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

### Payload: Quiz Completed

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

## Vérification de la Signature

### En PHP
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$secret = 'votre-clé-secrète-unique';

$expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected_signature, $signature)) {
    // La signature est valide
} else {
    // Rejeter la requête
}
```

### En JavaScript/Node.js
```javascript
const crypto = require('crypto');

const payload = req.rawBody; // ou req.body comme string
const signature = req.headers['x-webhook-signature'];
const secret = 'votre-clé-secrète-unique';

const expectedSignature = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');

if (crypto.timingSafeEqual(expectedSignature, signature)) {
    // La signature est valide
} else {
    // Rejeter la requête
}
```

### En Python
```python
import hashlib
import hmac

payload = request.get_data()
signature = request.headers.get('X-Webhook-Signature')
secret = 'votre-clé-secrète-unique'

expected_signature = 'sha256=' + hmac.new(
    secret.encode(),
    payload,
    hashlib.sha256
).hexdigest()

if hmac.compare_digest(expected_signature, signature):
    # La signature est valide
else:
    # Rejeter la requête
```

## Installation et Migration

### 1. Exécuter la migration
```bash
php bin/console doctrine:migrations:migrate
```

Cela créera:
- La colonne `started_at` dans la table `quiz_result`
- La table `webhook_subscription`

### 2. Accès à l'interface Admin
Les administrateurs peuvent accéder à `/admin/webhooks` pour gérer les webhooks

## Flux complet d'utilisation

### Côté étudiant
1. L'étudiant navigue vers le dashboard
2. Clique sur un cours puis sur "Commencer Quiz"
3. Le système crée une session `QuizResult` avec `started_at = maintenant`
4. **Un webhook `quiz_started` est déclenché**
5. L'étudiant passe le quiz normalement
6. Soumet les réponses
7. **Un webhook `quiz_completed` est déclenché** avec le score
8. L'étudiant voit ses résultats

### Côté administrateur
1. Va dans `/admin/webhooks`
2. Clique "Créer un nouveau webhook"
3. Entre son URL de réception (ex: `https://mon-dashboard.com/webhook/quiz`)
4. Choisit le type d'événement
5. Reçoit une clé secrète unique
6. Peut tester le webhook avant de l'activer
7. Voit le tableau de bord avec le statut de tous les webhooks

### Côté système de réception
1. Reçoit le POST request sur l'URL configurée
2. Vérifie la signature avec la clé secrète
3. Parse le JSON du payload
4. Traite les données (enregistrer, notifier, mettre à jour dashboard)
5. Retourne HTTP 200 pour confirmer
6. Si erreur: le système réessaye 3 fois avec délai

## Fichiers modifiés/créés

### Entités
- `src/Entity/QuizResult.php` - Ajout de `startedAt`
- `src/Entity/WebhookSubscription.php` - Nouvelle entité

### Services
- `src/Service/QuizNotificationService.php` - Gestion des notifications
- `src/Service/WebhookDispatcher.php` - Envoi des webhooks

### Controllers
- `src/Controller/Front/QuizController.php` - Modification de `start()` et `submit()`
- `src/Controller/Api/WebhookApiController.php` - API REST
- `src/Controller/Admin/WebhookAdminController.php` - Interface Admin

### Repositories
- `src/Repository/WebhookSubscriptionRepository.php` - Nouvelle repository

### Templates
- `templates/admin/webhooks/index.html.twig`
- `templates/admin/webhooks/new.html.twig`
- `templates/admin/webhooks/edit.html.twig`
- `templates/admin/webhooks/status.html.twig`

### Migrations
- `migrations/Version20251206190000.php` - Migration Doctrine

## Configuration recommandée

### .env
```env
# Peut être ajouté si nécessaire:
WEBHOOK_TIMEOUT=10
WEBHOOK_MAX_RETRIES=3
```

## Tests

### Tester via l'interface Admin
1. Créer un webhook avec une URL test (ex: webhook.site)
2. Cliquer "Tester"
3. Vérifier que la notification arrive

### Tester via curl
```bash
# Récupérer les webhooks
curl -X GET http://localhost:8000/api/webhooks \
  -H "Authorization: Bearer YOUR_TOKEN"

# Créer un webhook
curl -X POST http://localhost:8000/api/webhooks \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://webhook.site/unique-id",
    "eventType": "quiz_started"
  }'

# Tester un webhook
curl -X POST http://localhost:8000/api/webhooks/1/test \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Dépannage

### Les webhooks ne sont pas envoyés
1. Vérifier que le webhook est actif
2. Vérifier l'URL du webhook
3. Vérifier les logs dans `var/log/`
4. Vérifier la colonne `failureCount`

### Les webhooks sont désactivés automatiquement
- Cela se produit après 10 échecs consécutifs
- Vérifier l'URL du webhook
- Vérifier la connectivité réseau
- Réactiver manuellement après correction

### La signature ne correspond pas
- Vérifier que la clé secrète est correcte
- Vérifier que le payload n'a pas été modifié
- Utiliser `hash_equals()` ou équivalent pour éviter les timing attacks

## Monitoring et Logs

Les activités des webhooks sont logées dans `var/log/`

Exemple de log:
```
[2024-12-06 19:00:15] app.INFO: Quiz started notification sent {"quiz_id":10,"user_id":45,"webhooks_count":2}
[2024-12-06 19:00:16] app.INFO: Webhook dispatched successfully {"webhook_id":1,"url":"https://webhook.site/abc","status":200}
```

## Cas d'utilisation possibles

1. **Dashboard Admin en temps réel**
   - Afficher instantanément quels étudiants commencent des quiz
   - Afficher les scores au fur et à mesure

2. **Notifications par email**
   - Envoyer un email quand un étudiant commence/termine
   - Alerter si beaucoup d'étudiants commencent au même moment

3. **Intégration LMS**
   - Synchroniser avec Moodle/Canvas via API
   - Mettre à jour les grades automatiquement

4. **Analytics**
   - Enregistrer dans un système analytics
   - Générer des rapports

5. **Slack/Teams**
   - Envoyer des notifications sur Slack/Teams
   - Mention des instructeurs

## Support et Assistance

Pour des questions sur:
- L'implémentation: Voir `src/Service/`
- La configuration: Voir `.env`
- L'utilisation: Voir `/admin/webhooks`
