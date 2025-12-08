#!/usr/bin/env node

/**
 * Exemple d'implémentation Webhook en Node.js avec Express
 * Pour recevoir et traiter les notifications de quiz
 */

const express = require('express');
const crypto = require('crypto');
const app = express();

app.use(express.json());

// Configuration
const WEBHOOK_SECRET = 'votre_secret_webhook_ici';
const PORT = 3000;

/**
 * Middleware de vérification de signature HMAC
 */
function verifyWebhookSignature(req, res, next) {
  const signature = req.headers['x-webhook-signature'];
  const event = req.headers['x-webhook-event'];

  if (!signature) {
    return res.status(400).json({ error: 'Missing X-Webhook-Signature header' });
  }

  // Reconstruire le JSON exact reçu
  const payload = JSON.stringify(req.body);

  // Générer la signature attendue
  const expectedSignature = 'sha256=' + crypto
    .createHmac('sha256', WEBHOOK_SECRET)
    .update(payload)
    .digest('hex');

  // Vérifier de manière sûre (timing-safe)
  const isValid = crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expectedSignature)
  );

  if (!isValid) {
    console.error('Invalid webhook signature');
    return res.status(401).json({ error: 'Invalid signature' });
  }

  req.webhookEvent = event;
  req.webhookData = req.body;
  next();
}

/**
 * Route pour recevoir les webhooks
 */
app.post('/webhook/quiz', verifyWebhookSignature, (req, res) => {
  const { event, timestamp, data } = req.body;

  console.log(`\n✅ Webhook reçu: ${event}`);
  console.log(`📅 Timestamp: ${timestamp}`);

  if (event === 'quiz_started') {
    handleQuizStarted(data);
  } else if (event === 'quiz_completed') {
    handleQuizCompleted(data);
  }

  // Répondre au serveur
  res.json({
    success: true,
    message: 'Webhook processed successfully',
    processed_at: new Date().toISOString()
  });
});

/**
 * Traiter un quiz commencé
 */
function handleQuizStarted(data) {
  const { student, quiz, course } = data;

  console.log(`
📚 QUIZ COMMENCÉ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Étudiant: ${student.fullName}
Email: ${student.email}

Cours: ${course.title}
Quiz: ${quiz.title}
Points: ${quiz.totalPoints}
Temps limite: ${quiz.timeLimit}s (${Math.floor(quiz.timeLimit / 60)} min)

Commencé à: ${data.startedAt}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  `);

  // Exemple: Envoyer une notification par email
  sendEmailNotification(
    'admin@elearning.com',
    `${student.fullName} a commencé le quiz: ${quiz.title}`,
    data
  );

  // Exemple: Enregistrer dans une base de données
  logQuizActivity('quiz_started', data);

  // Exemple: Envoyer une alerte Slack
  sendSlackNotification(`:thinking_face: ${student.fullName} a commencé le quiz **${quiz.title}**`);
}

/**
 * Traiter un quiz complété
 */
function handleQuizCompleted(data) {
  const { student, quiz, course, result } = data;
  const scorePercentage = (result.score / quiz.totalPoints * 100).toFixed(2);

  console.log(`
🎉 QUIZ COMPLÉTÉ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Étudiant: ${student.fullName}
Email: ${student.email}

Cours: ${course.title}
Quiz: ${quiz.title}

Score: ${result.score}/${quiz.totalPoints} (${scorePercentage}%)
Résultat: ${result.passed ? '✅ RÉUSSI' : '❌ ÉCHOUÉ'}

Durée: ${Math.floor((new Date(result.completedAt) - new Date(result.startedAt)) / 1000)}s
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  `);

  // Exemple: Envoyer une notification par email
  const subject = result.passed
    ? `${student.fullName} a réussi le quiz: ${quiz.title}`
    : `${student.fullName} n'a pas réussi le quiz: ${quiz.title}`;

  sendEmailNotification('admin@elearning.com', subject, data);

  // Exemple: Mettre à jour les statistiques
  updateStudentStats(student.id, result);

  // Exemple: Envoyer une alerte Slack
  const emoji = result.passed ? ':tada:' : ':warning:';
  sendSlackNotification(
    `${emoji} ${student.fullName} a complété **${quiz.title}** avec ${scorePercentage}%`
  );
}

/**
 * Fonctions d'aide (à implémenter selon vos besoins)
 */

function sendEmailNotification(to, subject, data) {
  console.log(`📧 Email envoyé à ${to}: ${subject}`);
  // Implémenter avec nodemailer, SendGrid, etc.
}

function logQuizActivity(type, data) {
  console.log(`📝 Activité enregistrée: ${type}`);
  // Implémenter la sauvegarde en base de données
}

function sendSlackNotification(message) {
  console.log(`💬 Slack: ${message}`);
  // Implémenter avec axios/fetch vers l'API Slack
}

function updateStudentStats(studentId, result) {
  console.log(`📊 Statistiques mises à jour pour l'étudiant ${studentId}`);
  // Implémenter la mise à jour des stats
}

// Démarrer le serveur
app.listen(PORT, () => {
  console.log(`\n🚀 Serveur webhook écoutant sur le port ${PORT}`);
  console.log(`📍 URL: http://localhost:${PORT}/webhook/quiz`);
  console.log(`🔐 Secret HMAC configuré: ${WEBHOOK_SECRET}\n`);
});

/**
 * Gestion des erreurs
 */
app.use((err, req, res, next) => {
  console.error('Erreur serveur:', err);
  res.status(500).json({ error: 'Internal server error' });
});

module.exports = app;
