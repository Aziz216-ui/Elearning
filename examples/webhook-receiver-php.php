<?php
/**
 * Exemple d'implémentation Webhook en PHP
 * Pour recevoir et traiter les notifications de quiz
 */

class WebhookReceiver
{
    private const WEBHOOK_SECRET = 'votre_secret_webhook_ici';

    /**
     * Vérifier la signature HMAC-SHA256
     */
    public static function verifySignature(string $signature, string $payload): bool
    {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, self::WEBHOOK_SECRET);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Traiter la requête webhook
     */
    public static function handleWebhook(): void
    {
        // Vérifier la méthode
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }

        // Récupérer les en-têtes
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
        $event = $_SERVER['HTTP_X_WEBHOOK_EVENT'] ?? 'unknown';

        if (empty($signature)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Missing X-Webhook-Signature header']));
        }

        // Récupérer le payload brut
        $payload = file_get_contents('php://input');

        // Vérifier la signature
        if (!self::verifySignature($signature, $payload)) {
            http_response_code(401);
            error_log('❌ Signature webhook invalide');
            exit(json_encode(['error' => 'Invalid signature']));
        }

        // Décoder le JSON
        $data = json_decode($payload, true);

        if (!$data) {
            http_response_code(400);
            exit(json_encode(['error' => 'Invalid JSON']));
        }

        echo "[" . date('Y-m-d H:i:s') . "] ✅ Webhook reçu: $event\n";
        echo "[" . date('Y-m-d H:i:s') . "] 📅 Timestamp: " . ($data['timestamp'] ?? 'N/A') . "\n";

        // Router selon le type d'événement
        switch ($event) {
            case 'quiz_started':
                self::handleQuizStarted($data['data'] ?? []);
                break;
            case 'quiz_completed':
                self::handleQuizCompleted($data['data'] ?? []);
                break;
            default:
                echo "Événement inconnu: $event\n";
        }

        // Répondre au serveur
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'processed_at' => date('c')
        ]);
    }

    /**
     * Traiter un quiz commencé
     */
    private static function handleQuizStarted(array $data): void
    {
        $student = $data['student'] ?? [];
        $quiz = $data['quiz'] ?? [];
        $course = $data['course'] ?? [];

        $message = <<<EOT

📚 QUIZ COMMENCÉ
=================================================
Étudiant: {$student['fullName'] ?? 'N/A'}
Email: {$student['email'] ?? 'N/A'}

Cours: {$course['title'] ?? 'N/A'}
Quiz: {$quiz['title'] ?? 'N/A'}
Points: {$quiz['totalPoints'] ?? 'N/A'}
Temps limite: {$quiz['timeLimit'] ?? 'N/A'}s ({$this->formatSeconds($quiz['timeLimit'] ?? 0)} min)

Commencé à: {$data['startedAt'] ?? 'N/A'}
=================================================

EOT;

        echo $message;

        // Exemple: Envoyer une notification
        self::sendEmailNotification(
            'admin@elearning.com',
            "{$student['fullName']} a commencé le quiz: {$quiz['title']}",
            $data
        );

        // Exemple: Enregistrer l'activité
        self::logQuizActivity('quiz_started', $data);

        // Exemple: Envoyer une alerte Slack
        self::sendSlackNotification(
            ":thinking_face: {$student['fullName']} a commencé le quiz **{$quiz['title']}**"
        );
    }

    /**
     * Traiter un quiz complété
     */
    private static function handleQuizCompleted(array $data): void
    {
        $student = $data['student'] ?? [];
        $quiz = $data['quiz'] ?? [];
        $course = $data['course'] ?? [];
        $result = $data['result'] ?? [];

        $totalPoints = $quiz['totalPoints'] ?? 1;
        $score = $result['score'] ?? 0;
        $scorePercentage = ($totalPoints > 0) ? ($score / $totalPoints * 100) : 0;
        $passed = $result['passed'] ?? false;

        $statusEmoji = $passed ? '✅ RÉUSSI' : '❌ ÉCHOUÉ';
        $duration = self::calculateDuration($result['startedAt'] ?? '', $result['completedAt'] ?? '');

        $message = <<<EOT

🎉 QUIZ COMPLÉTÉ
=================================================
Étudiant: {$student['fullName'] ?? 'N/A'}
Email: {$student['email'] ?? 'N/A'}

Cours: {$course['title'] ?? 'N/A'}
Quiz: {$quiz['title'] ?? 'N/A'}

Score: $score/$totalPoints (" . sprintf('%.2f', $scorePercentage) . "%)
Résultat: $statusEmoji

Durée: ${duration}s
=================================================

EOT;

        echo $message;

        // Exemple: Envoyer une notification
        $subject = $passed
            ? "{$student['fullName']} a réussi le quiz: {$quiz['title']}"
            : "{$student['fullName']} n'a pas réussi le quiz: {$quiz['title']}";

        self::sendEmailNotification('admin@elearning.com', $subject, $data);

        // Exemple: Mettre à jour les statistiques
        self::updateStudentStats($student['id'] ?? null, $result);

        // Exemple: Envoyer une alerte Slack
        $emoji = $passed ? ':tada:' : ':warning:';
        self::sendSlackNotification(
            "$emoji {$student['fullName']} a complété **{$quiz['title']}** avec " .
            sprintf('%.2f', $scorePercentage) . "%"
        );
    }

    /**
     * Calculer la durée entre deux timestamps
     */
    private static function calculateDuration(string $startedAt, string $completedAt): int
    {
        try {
            $start = new \DateTime($startedAt);
            $end = new \DateTime($completedAt);
            return (int)$end->diff($start)->format('%s');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Formater les secondes en minutes
     */
    private static function formatSeconds(int $seconds): int
    {
        return (int)($seconds / 60);
    }

    /**
     * Envoyer une notification par email
     */
    private static function sendEmailNotification(string $to, string $subject, array $data): void
    {
        echo "📧 Email envoyé à $to: $subject\n";
        // TODO: Implémenter avec mail(), PHPMailer, ou autre
    }

    /**
     * Enregistrer l'activité du quiz
     */
    private static function logQuizActivity(string $activityType, array $data): void
    {
        echo "📝 Activité enregistrée: $activityType\n";
        // TODO: Implémenter la sauvegarde en base de données
    }

    /**
     * Envoyer une notification Slack
     */
    private static function sendSlackNotification(string $message): void
    {
        echo "💬 Slack: $message\n";
        // TODO: Implémenter avec curl vers l'API Slack
    }

    /**
     * Mettre à jour les statistiques de l'étudiant
     */
    private static function updateStudentStats(?int $studentId, array $result): void
    {
        echo "📊 Statistiques mises à jour pour l'étudiant $studentId\n";
        // TODO: Implémenter la mise à jour des stats
    }
}

// Point d'entrée
if (php_sapi_name() !== 'cli') {
    echo "Ce script doit être appelé en tant que gestionnaire webhook.\n";
    echo "Configurez-le dans votre serveur web ou framework.\n";
} else {
    WebhookReceiver::handleWebhook();
}
?>
