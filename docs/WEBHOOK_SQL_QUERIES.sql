-- ========================================
-- Gestion des Webhooks - Requêtes SQL
-- ========================================

-- 1. AFFICHAGE DES WEBHOOKS
-- ========================================

-- Voir tous les webhooks
SELECT 
    ws.id,
    ws.url,
    ws.event_type,
    ws.is_active,
    ws.created_at,
    ws.last_triggered_at,
    ws.failure_count,
    u.email as admin_email
FROM webhook_subscription ws
JOIN `user` u ON ws.admin_id = u.id
ORDER BY ws.created_at DESC;

-- Voir les webhooks actifs
SELECT 
    ws.id,
    ws.url,
    ws.event_type,
    ws.created_at,
    u.email as admin_email
FROM webhook_subscription ws
JOIN `user` u ON ws.admin_id = u.id
WHERE ws.is_active = 1
ORDER BY ws.created_at DESC;

-- Voir les webhooks échoués
SELECT 
    ws.id,
    ws.url,
    ws.event_type,
    ws.failure_count,
    ws.last_triggered_at,
    u.email as admin_email
FROM webhook_subscription ws
JOIN `user` u ON ws.admin_id = u.id
WHERE ws.failure_count > 0
ORDER BY ws.failure_count DESC;

-- Voir les webhooks d'un admin spécifique
SELECT 
    ws.id,
    ws.url,
    ws.event_type,
    ws.is_active,
    ws.failure_count,
    ws.last_triggered_at
FROM webhook_subscription ws
JOIN `user` u ON ws.admin_id = u.id
WHERE u.email = 'admin@example.com'
ORDER BY ws.created_at DESC;

-- Voir les webhooks par type d'événement
SELECT 
    ws.event_type,
    COUNT(*) as total,
    SUM(CASE WHEN ws.is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN ws.failure_count > 0 THEN 1 ELSE 0 END) as with_errors
FROM webhook_subscription ws
GROUP BY ws.event_type;


-- 2. GESTION DES WEBHOOKS
-- ========================================

-- Réactiver un webhook
UPDATE webhook_subscription 
SET is_active = 1, failure_count = 0
WHERE id = 1;

-- Désactiver un webhook
UPDATE webhook_subscription 
SET is_active = 0
WHERE id = 1;

-- Réinitialiser les erreurs
UPDATE webhook_subscription 
SET failure_count = 0, is_active = 1
WHERE id = 1;

-- Réinitialiser tous les webhooks échoués
UPDATE webhook_subscription 
SET failure_count = 0, is_active = 1
WHERE failure_count > 0;

-- Changer l'URL d'un webhook
UPDATE webhook_subscription 
SET url = 'https://new-url.com/webhook'
WHERE id = 1;

-- Changer le type d'événement
UPDATE webhook_subscription 
SET event_type = 'quiz_completed'
WHERE id = 1;


-- 3. SUPPRESSION DES WEBHOOKS
-- ========================================

-- Supprimer un webhook
DELETE FROM webhook_subscription 
WHERE id = 1;

-- Supprimer tous les webhooks d'un admin
DELETE FROM webhook_subscription 
WHERE admin_id = (SELECT id FROM `user` WHERE email = 'admin@example.com');

-- Supprimer les webhooks échoués
DELETE FROM webhook_subscription 
WHERE failure_count >= 10;

-- Supprimer les webhooks inactifs
DELETE FROM webhook_subscription 
WHERE is_active = 0 AND failure_count > 0;


-- 4. MAINTENANCE ET STATISTIQUES
-- ========================================

-- Voir l'historique des déclenchements
SELECT 
    ws.id,
    ws.url,
    ws.event_type,
    ws.last_triggered_at,
    TIMESTAMPDIFF(MINUTE, ws.last_triggered_at, NOW()) as minutes_since_last_trigger,
    ws.failure_count,
    u.email as admin_email
FROM webhook_subscription ws
JOIN `user` u ON ws.admin_id = u.id
WHERE ws.last_triggered_at IS NOT NULL
ORDER BY ws.last_triggered_at DESC;

-- Voir les webhooks qui n'ont jamais été déclenchés
SELECT 
    ws.id,
    ws.url,
    ws.event_type,
    ws.created_at,
    u.email as admin_email
FROM webhook_subscription ws
JOIN `user` u ON ws.admin_id = u.id
WHERE ws.last_triggered_at IS NULL
ORDER BY ws.created_at DESC;

-- Voir le nombre de webhooks par admin
SELECT 
    u.email,
    COUNT(*) as total_webhooks,
    SUM(CASE WHEN ws.is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN ws.failure_count > 0 THEN 1 ELSE 0 END) as with_errors
FROM webhook_subscription ws
JOIN `user` u ON ws.admin_id = u.id
GROUP BY u.id, u.email;


-- 5. RÉGÉNÉRATION DE SECRETS
-- ========================================

-- Régénérer le secret d'un webhook
-- Note: En PHP, utilisez: bin2hex(random_bytes(32))
-- Exemple de valeur: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z

UPDATE webhook_subscription 
SET secret = 'nouvelle_valeur_secret'
WHERE id = 1;


-- 6. ANALYSES AVANCÉES
-- ========================================

-- Taux de succès/échec des webhooks
SELECT 
    ws.event_type,
    COUNT(*) as total_webhooks,
    SUM(CASE WHEN ws.is_active = 1 AND ws.failure_count = 0 THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN ws.failure_count > 0 THEN 1 ELSE 0 END) as failed,
    ROUND(
        SUM(CASE WHEN ws.is_active = 1 AND ws.failure_count = 0 THEN 1 ELSE 0 END) / 
        COUNT(*) * 100, 2
    ) as success_rate_percent
FROM webhook_subscription ws
GROUP BY ws.event_type;

-- Webhooks les plus récemment déclenchés
SELECT 
    ws.id,
    ws.url,
    ws.event_type,
    ws.last_triggered_at,
    ws.failure_count,
    u.email as admin_email
FROM webhook_subscription ws
JOIN `user` u ON ws.admin_id = u.id
WHERE ws.last_triggered_at IS NOT NULL
ORDER BY ws.last_triggered_at DESC
LIMIT 10;

-- Webhooks avec le plus d'erreurs
SELECT 
    ws.id,
    ws.url,
    ws.event_type,
    ws.failure_count,
    ws.is_active,
    u.email as admin_email
FROM webhook_subscription ws
JOIN `user` u ON ws.admin_id = u.id
ORDER BY ws.failure_count DESC
LIMIT 10;


-- ========================================
-- NOTES IMPORTANTES
-- ========================================
-- 
-- 1. Les secrets HMAC sont générés automatiquement avec bin2hex(random_bytes(32))
-- 2. Après 10 échecs consécutifs, le webhook est automatiquement désactivé
-- 3. Les timestamps are en UTC (datetime_immutable)
-- 4. is_active: 1 = actif, 0 = inactif
-- 5. Pour réinitialiser un webhook, mettez failure_count à 0
-- 
-- ========================================
