# Guide d'Installation - Système de Webhooks Elearning

## 📋 Prérequis

- PHP 8.0+
- Symfony 6.0+
- Composer
- Base de données MySQL 5.7+
- Accès administrateur

## 🚀 Installation Pas à Pas

### Étape 1: Mettre à jour le code

```bash
cd /chemin/vers/elearning

# Récupérer les derniers changements
git pull origin bouu

# Ou manuellement copier les fichiers
```

### Étape 2: Vérifier les fichiers installés

Vérifier que les fichiers suivants existent:

```
✓ src/Entity/WebhookSubscription.php
✓ src/Service/QuizNotificationService.php
✓ src/Service/WebhookDispatcher.php
✓ src/Controller/Api/WebhookApiController.php
✓ src/Controller/Admin/WebhookAdminController.php
✓ src/Repository/WebhookSubscriptionRepository.php
✓ migrations/Version20251206190000.php
✓ examples/webhook_receiver_*.{php,js,py}
```

### Étape 3: Mettre à jour les dépendances

Les services utilisent `Symfony\Contracts\HttpClient\HttpClientInterface` qui est déjà inclus.

```bash
composer install
```

### Étape 4: Exécuter la migration

```bash
# Pour développement
php bin/console doctrine:migrations:migrate

# Pour production
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
```

**Cela crée**:
- Table `webhook_subscription`
- Colonne `started_at` dans `quiz_result`

### Étape 5: Configurer les variables d'environnement

Ajouter au fichier `.env` (ou `.env.local`):

```bash
# Clé secrète pour les webhooks (générer une nouvelle valeur)
WEBHOOK_SECRET=change-this-to-a-secure-random-string

# Configuration optionnelle
WEBHOOK_TIMEOUT=10
WEBHOOK_MAX_RETRIES=3
WEBHOOK_RETRY_DELAY=2
WEBHOOK_FAILURE_THRESHOLD=10
```

**Pour générer une clé sécurisée**:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Étape 6: Vérifier l'installation

```bash
# Vérifier que la table existe
php bin/console doctrine:query:sql "SELECT * FROM webhook_subscription LIMIT 1;"

# Vérifier que la colonne existe
php bin/console doctrine:query:sql "SELECT started_at FROM quiz_result LIMIT 1;"

# Vérifier les services
php bin/console debug:container | grep -i webhook
```

### Étape 7: Accéder à l'interface Admin

1. Allez à `http://localhost:8000/admin/webhooks`
2. Connectez-vous avec un compte administrateur
3. Vous devriez voir la page vide des webhooks

## ✅ Vérification de l'installation

### 1️⃣ Créer votre premier webhook

1. Allez à `/admin/webhooks`
2. Cliquez "Créer un nouveau webhook"
3. Entrez:
   - URL: `https://webhook.site/unique-id` (obtenir sur webhook.site)
   - Type d'événement: `quiz_started`
4. Cliquez "Créer le webhook"
5. **Copiez la clé secrète** (affichée après création)

### 2️⃣ Tester le webhook

```bash
# Via l'interface
# Allez à /admin/webhooks et cliquez le bouton 🧪 "Tester"

# Via CLI
php bin/console webhook:test 1
```

### 3️⃣ Vérifier webhook.site

1. Allez à votre URL webhook.site
2. Vous devriez voir la requête POST reçue
3. Vérifier le payload JSON

### 4️⃣ Tester avec un vrai quiz

1. Connectez-vous comme étudiant
2. Allez à un cours
3. Cliquez "Commencer Quiz"
4. **L'événement webhook doit être déclenché**
5. Vérifier sur webhook.site que le payload a été reçu

## 📝 Configuration par type de récepteur

### Configuration pour Slack

```bash
# 1. Créer un webhook Slack sur https://api.slack.com/apps

# 2. Créer un webhook Elearning:
# URL: https://hooks.slack.com/services/YOUR/WEBHOOK/URL
# Type: quiz_started

# 3. Test automatique via l'interface
```

### Configuration pour Email

```bash
# Créer une intégration email personnalisée
# Voir: examples/webhook_receiver_nodejs.js (section Slack adaptée)

# Utiliser un service comme:
# - SendGrid
# - Mailgun
# - SMTP simple
```

### Configuration pour un Dashboard

```bash
# 1. Utiliser Node.js + Express
# Code: examples/webhook_receiver_nodejs.js

# 2. Implémenter les événements (voir le fichier)

# 3. Intégrer avec WebSocket pour temps réel
```

## 🐛 Dépannage

### Le webhook ne s'envoie pas

```bash
# 1. Vérifier que le webhook est actif
SELECT * FROM webhook_subscription WHERE id = 1\G

# 2. Vérifier qu'il existe des webhooks
php bin/console debug:container | grep webhook

# 3. Activer le debug mode
tail -f var/log/dev.log | grep -i webhook

# 4. Vérifier la base de données
php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM webhook_subscription WHERE is_active = 1;"
```

### Erreur "WEBHOOK_SECRET not configured"

```bash
# 1. Ajouter WEBHOOK_SECRET au .env
echo "WEBHOOK_SECRET=your-secret-key" >> .env

# 2. Recharger les services
php bin/console cache:clear
```

### Erreur "Table webhook_subscription doesn't exist"

```bash
# Exécuter la migration
php bin/console doctrine:migrations:migrate

# Si ça ne fonctionne pas, créer manuellement:
php bin/console doctrine:schema:update --force
```

### Le webhook est marqué "Inactif"

```bash
# Les webhooks sont automatiquement désactivés après 10 échecs
# Pour réactiver:

# Via interface:
# /admin/webhooks/{id}/edit -> Cocher "Actif" -> Enregistrer

# Via SQL:
UPDATE webhook_subscription SET is_active = 1, failure_count = 0 WHERE id = 1;
```

## 📚 Documentation complète

Voir les fichiers:
- `WEBHOOK_DOCUMENTATION.md` - Documentation technique complète
- `README_WEBHOOKS.md` - Guide rapide
- `examples/` - Exemples d'implémentation

## 🔄 Mise à jour

Pour mettre à jour dans le futur:

```bash
# Récupérer les changements
git pull origin bouu

# Mettre à jour les dépendances
composer install

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

## ✨ Fonctionnalités prêtes à l'emploi

✅ Créer/modifier/supprimer des webhooks  
✅ Tester les webhooks  
✅ Voir le statut détaillé  
✅ Signature HMAC-SHA256  
✅ Retry automatique  
✅ Notifications quiz_started  
✅ Notifications quiz_completed  
✅ API REST complète  
✅ Interface Admin intuitive  

## 🎯 Prochaines étapes

1. **Créer le premier webhook** sur `/admin/webhooks`
2. **Tester** avec l'interface
3. **Intégrer** avec votre système (Slack, Email, Dashboard, etc.)
4. **Monitorer** sur `/admin/webhooks/status`

## 📞 Support

- Questions sur l'installation?
  - Vérifier `WEBHOOK_DOCUMENTATION.md`
  - Consulter les `examples/`
  - Vérifier les logs `var/log/dev.log`

- Besoin d'aide?
  - Tester via `/admin/webhooks`
  - Utiliser la commande `webhook:test`
  - Consulter la section dépannage ci-dessus

---

**Installation complète**: ~5-10 minutes  
**Première notification**: Immédiate après test  
**Statut**: ✅ Production-ready
