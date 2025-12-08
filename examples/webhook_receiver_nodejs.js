/**
 * Exemple d'implémentation d'un récepteur de webhook avec Express.js
 * 
 * npm install express body-parser crypto dotenv
 */

const express = require('express');
const crypto = require('crypto');
const bodyParser = require('body-parser');
require('dotenv').config();

const app = express();

// ==================== MIDDLEWARES ====================

// Middleware pour capturer le body brut (nécessaire pour vérifier la signature)
app.use((req, res, next) => {
    let rawBody = '';
    req.on('data', chunk => {
        rawBody += chunk.toString();
    });
    req.on('end', () => {
        req.rawBody = rawBody;
        next();
    });
});

// Parser JSON après avoir capturé le body brut
app.use(bodyParser.json());

// ==================== UTILITAIRES ====================

/**
 * Vérifier la signature HMAC-SHA256
 */
function verifySignature(payload, signature, secret) {
    const expectedSignature = 'sha256=' + crypto
        .createHmac('sha256', secret)
        .update(payload)
        .digest('hex');

    return crypto.timingSafeEqual(
        Buffer.from(expectedSignature),
        Buffer.from(signature)
    );
}

/**
 * Gérer l'événement quiz_started
 */
async function handleQuizStarted(data) {
    const { student, quiz, course, startedAt } = data;

    console.log(`
        📚 QUIZ COMMENCÉ
        Étudiant: ${student.fullName} (${student.email})
        Quiz: ${quiz.title}
        Cours: ${course.title}
        Heure: ${startedAt}
    `);

    // TODO: Implémenter votre logique métier
    // - Envoyer une notification Slack/Teams
    // - Mettre à jour une base de données
    // - Mettre à jour un dashboard en temps réel (WebSocket)
    // - Alerter les instructeurs
    // - etc.
}

/**
 * Gérer l'événement quiz_completed
 */
async function handleQuizCompleted(data) {
    const { student, quiz, result } = data;
    const passed = result.passed ? '✅' : '❌';

    console.log(`
        ${passed} QUIZ COMPLÉTÉ
        Étudiant: ${student.fullName} (${student.email})
        Quiz: ${quiz.title}
        Score: ${result.score}/${quiz.totalPoints} (${(result.score / quiz.totalPoints * 100).toFixed(1)}%)
        Statut: ${result.passed ? 'RÉUSSI' : 'ÉCHOUÉ'}
        Durée: ${calculateDuration(result.startedAt, result.completedAt)}
    `);

    // TODO: Implémenter votre logique métier
    // - Mettre à jour les grades dans le LMS
    // - Générer un certificat
    // - Envoyer un email de résultat
    // - Mettre à jour les statistiques
    // - etc.
}

/**
 * Calculer la durée entre deux timestamps
 */
function calculateDuration(startedAt, completedAt) {
    const start = new Date(startedAt);
    const end = new Date(completedAt);
    const diffSeconds = (end - start) / 1000;
    
    const minutes = Math.floor(diffSeconds / 60);
    const seconds = diffSeconds % 60;
    
    return `${minutes}m ${Math.round(seconds)}s`;
}

// ==================== ROUTES ====================

/**
 * Route principale pour recevoir les webhooks
 * POST /webhook/quiz
 */
app.post('/webhook/quiz', async (req, res) => {
    try {
        // 1. Vérifier la signature
        const signature = req.headers['x-webhook-signature'];
        const secret = process.env.WEBHOOK_SECRET;

        if (!secret) {
            return res.status(500).json({
                success: false,
                error: 'Webhook secret not configured'
            });
        }

        if (!signature) {
            return res.status(401).json({
                success: false,
                error: 'Missing X-Webhook-Signature header'
            });
        }

        // Vérifier la signature du payload
        if (!verifySignature(req.rawBody, signature, secret)) {
            return res.status(401).json({
                success: false,
                error: 'Invalid signature'
            });
        }

        // 2. Récupérer le type d'événement
        const eventType = req.headers['x-webhook-event'];
        const payload = req.body;

        console.log(`\n[${new Date().toISOString()}] Webhook reçu: ${eventType}`);

        // 3. Traiter l'événement selon le type
        switch (payload.event) {
            case 'quiz_started':
                await handleQuizStarted(payload.data);
                break;

            case 'quiz_completed':
                await handleQuizCompleted(payload.data);
                break;

            default:
                return res.status(400).json({
                    success: false,
                    error: `Unknown event type: ${payload.event}`
                });
        }

        // 4. Répondre avec succès
        res.status(200).json({
            success: true,
            message: 'Event processed successfully',
            event: payload.event
        });

    } catch (error) {
        console.error('Error processing webhook:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

/**
 * Route de health check
 */
app.get('/health', (req, res) => {
    res.status(200).json({
        status: 'healthy',
        timestamp: new Date().toISOString()
    });
});

// ==================== DÉMARRAGE DU SERVEUR ====================

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
    console.log(`
╔════════════════════════════════════════╗
║   Webhook Receiver lancé sur port ${PORT}    ║
║   URL: http://localhost:${PORT}          ║
║   Endpoint: /webhook/quiz               ║
╚════════════════════════════════════════╝
    `);
});

// ==================== EXEMPLES D'UTILISATION ====================

/*

1. AVEC NGROK (pour tester localement):
   - npm install -g ngrok
   - ngrok http 3000
   - Utiliser l'URL générée comme URL du webhook dans l'admin
   - Exemple: https://abc123.ngrok.io/webhook/quiz

2. AVEC WEBHOOK.SITE:
   - Aller sur https://webhook.site
   - Copier l'URL générée
   - L'utiliser comme URL du webhook dans l'admin

3. AVEC DOCKER:
   - docker build -t elearning-webhook-receiver .
   - docker run -e WEBHOOK_SECRET=your-secret -p 3000:3000 elearning-webhook-receiver

4. TESTER MANUELLEMENT:
   curl -X POST http://localhost:3000/webhook/quiz \
     -H "Content-Type: application/json" \
     -H "X-Webhook-Signature: sha256=..." \
     -H "X-Webhook-Event: quiz_started" \
     -d '{...payload...}'

5. INTÉGRATIONS POSSIBLES:
   - Slack: Envoyer une notification sur un channel Slack
   - Teams: Envoyer une notification sur Microsoft Teams
   - Discord: Envoyer une notification sur Discord
   - Email: Envoyer un email aux instructeurs
   - Database: Sauvegarder dans une base de données
   - WebSocket: Mettre à jour un dashboard en temps réel
*/

// ==================== EXEMPLE D'INTÉGRATION SLACK ====================

/*

const axios = require('axios');

async function notifySlack(event, data) {
    const webhookUrl = process.env.SLACK_WEBHOOK_URL;
    
    if (!webhookUrl) return;

    const student = data.student;
    const quiz = data.quiz;

    let color, title;
    
    if (event === 'quiz_started') {
        color = '#0099ff';
        title = '📚 Quiz commencé';
    } else {
        const passed = data.result.passed;
        color = passed ? '#00ff00' : '#ff0000';
        title = passed ? '✅ Quiz réussi' : '❌ Quiz échoué';
    }

    try {
        await axios.post(webhookUrl, {
            attachments: [{
                color,
                title,
                fields: [
                    {
                        title: 'Étudiant',
                        value: `${student.fullName} (${student.email})`,
                        short: false
                    },
                    {
                        title: 'Quiz',
                        value: quiz.title,
                        short: false
                    },
                    ...(event === 'quiz_completed' ? [{
                        title: 'Score',
                        value: `${data.result.score}/${quiz.totalPoints}`,
                        short: true
                    }] : [])
                ],
                ts: Math.floor(Date.now() / 1000)
            }]
        });
    } catch (error) {
        console.error('Error sending Slack notification:', error);
    }
}

// Dans handleQuizStarted:
// await notifySlack('quiz_started', data);

// Dans handleQuizCompleted:
// await notifySlack('quiz_completed', data);

*/
