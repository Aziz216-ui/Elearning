<?php

/**
 * Exemple d'implémentation d'un recepteur de webhook Elearning
 * 
 * Placez ce fichier à une URL accessible (ex: https://mon-dashboard.com/webhook/quiz)
 * Puis configurez cette URL dans l'admin des webhooks
 */

require_once __DIR__ . '/vendor/autoload.php';

class WebhookReceiver
{
    private string $secret;
    private array $payload;
    private string $signature;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Traiter la requête webhook entrante
     */
    public function handle(): void
    {
        try {
            // 1. Récupérer le payload brut
            $payload = file_get_contents('php://input');
            $this->payload = json_decode($payload, true);

            if (!$this->payload) {
                $this->sendError('Invalid JSON payload', 400);
                return;
            }

            // 2. Récupérer et vérifier la signature
            $this->signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

            if (!$this->verifySignature($payload)) {
                $this->sendError('Invalid signature', 401);
                return;
            }

            // 3. Traiter l'événement
            $this->processEvent();

            // 4. Répondre avec succès
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Event processed']);

        } catch (\Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Vérifier la signature HMAC-SHA256
     */
    private function verifySignature(string $payload): bool
    {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $this->secret);
        
        return hash_equals($expectedSignature, $this->signature);
    }

    /**
     * Traiter l'événement webhook
     */
    private function processEvent(): void
    {
        $event = $this->payload['event'] ?? 'unknown';

        switch ($event) {
            case 'quiz_started':
                $this->handleQuizStarted();
                break;
            case 'quiz_completed':
                $this->handleQuizCompleted();
                break;
            default:
                throw new \Exception("Unknown event type: {$event}");
        }
    }

    /**
     * Gérer l'événement quiz_started
     */
    private function handleQuizStarted(): void
    {
        $data = $this->payload['data'];
        $student = $data['student'];
        $quiz = $data['quiz'];
        $course = $data['course'];

        // Exemple: Enregistrer en base de données
        echo sprintf(
            "📚 L'étudiant %s (%s) a commencé le quiz '%s' du cours '%s'\n",
            $student['fullName'],
            $student['email'],
            $quiz['title'],
            $course['title']
        );

        // TODO: Implémenter votre logique métier
        // - Mettre à jour une table de log
        // - Envoyer une notification Slack
        // - Mettre à jour un dashboard
        // - etc.
    }

    /**
     * Gérer l'événement quiz_completed
     */
    private function handleQuizCompleted(): void
    {
        $data = $this->payload['data'];
        $student = $data['student'];
        $quiz = $data['quiz'];
        $result = $data['result'];

        $status = $result['passed'] ? '✅ RÉUSSI' : '❌ ÉCHOUÉ';

        echo sprintf(
            "%s L'étudiant %s (%s) a terminé le quiz '%s' avec un score de %d/%d\n",
            $status,
            $student['fullName'],
            $student['email'],
            $quiz['title'],
            $result['score'],
            $quiz['totalPoints']
        );

        // TODO: Implémenter votre logique métier
        // - Mettre à jour les grades
        // - Envoyer un certificat
        // - Mettre à jour un dashboard
        // - etc.
    }

    /**
     * Envoyer une réponse d'erreur
     */
    private function sendError(string $message, int $statusCode): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
    }
}

// ==================== POINT D'ENTRÉE ====================

// La clé secrète doit être stockée en variable d'environnement ou en config
$secret = $_ENV['WEBHOOK_SECRET'] ?? getenv('WEBHOOK_SECRET');

if (!$secret) {
    http_response_code(500);
    die(json_encode(['error' => 'Webhook secret not configured']));
}

$receiver = new WebhookReceiver($secret);
$receiver->handle();
