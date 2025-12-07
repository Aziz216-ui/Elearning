<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Entity\QuizResult;
use App\Entity\User;
use App\Repository\WebhookSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Service\NotificationService;
use App\Service\WebhookDispatcher;

class QuizNotificationService
{
    public function __construct(
        private WebhookSubscriptionRepository $webhookRepository,
        private WebhookDispatcher $webhookDispatcher,
        private LoggerInterface $logger,
        private NotificationService $notificationService
    ) {
    }

    /**
     * Notifie les admins qu'un étudiant a commencé un quiz
     */
    public function notifyQuizStarted(QuizResult $quizResult): void
    {
        try {
            // Récupérer les webhooks actifs pour cet événement
            $webhooks = $this->webhookRepository->findActiveWebhooksForEvent('quiz_started');

            $payload = $this->buildQuizStartedPayload($quizResult);

            // Envoyer les webhooks
            foreach ($webhooks as $webhook) {
                $this->webhookDispatcher->dispatch($webhook, $payload);
            }

            // Créer des notifications pour TOUS les admins
            $adminUsers = $this->em->getRepository('App\Entity\User')->createQueryBuilder('u')
                ->where('JSON_CONTAINS(u.roles, :role) = 1')
                ->setParameter('role', '"ROLE_ADMIN"')
                ->getQuery()
                ->getResult();
            
            foreach ($adminUsers as $admin) {
                $this->notificationService->notifyQuizStarted($admin, $payload['data']);
            }

            $this->logger->info('Quiz started notification sent', [
                'quiz_id' => $quizResult->getQuiz()->getId(),
                'user_id' => $quizResult->getUser()->getId(),
                'webhooks_count' => count($webhooks),
                'admins_notified' => count($adminUsers)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error notifying quiz started: ' . $e->getMessage());
        }
    }

    /**
     * Notifie les admins qu'un étudiant a complété un quiz
     */
    public function notifyQuizCompleted(QuizResult $quizResult): void
    {
        try {
            $webhooks = $this->webhookRepository->findActiveWebhooksForEvent('quiz_completed');

            if (empty($webhooks)) {
                $this->logger->info('Aucun webhook configuré pour quiz_completed');
                return;
            }

            $payload = $this->buildQuizCompletedPayload($quizResult);

            foreach ($webhooks as $webhook) {
                $this->webhookDispatcher->dispatch($webhook, $payload);
            }

            $this->logger->info('Quiz completed notification sent', [
                'quiz_id' => $quizResult->getQuiz()->getId(),
                'user_id' => $quizResult->getUser()->getId(),
                'score' => $quizResult->getScore()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error notifying quiz completed: ' . $e->getMessage());
        }
    }

    /**
     * Construit le payload pour l'événement quiz_started
     */
    private function buildQuizStartedPayload(QuizResult $quizResult): array
    {
        $user = $quizResult->getUser();
        $quiz = $quizResult->getQuiz();

        return [
            'event' => 'quiz_started',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTime::ATOM),
            'data' => [
                'quiz_result_id' => $quizResult->getId(),
                'student' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName() ?? ($user->getName() . ' ' . $user->getLastname()),
                ],
                'quiz' => [
                    'id' => $quiz->getId(),
                    'title' => $quiz->getTitle(),
                    'description' => $quiz->getDescription(),
                    'totalPoints' => $quiz->getTotalPoints(),
                    'timeLimit' => $quiz->getTimeLimit(),
                ],
                'course' => [
                    'id' => $quiz->getCours()?->getId(),
                    'title' => $quiz->getCours()?->getTitle(),
                ],
                'startedAt' => $quizResult->getStartedAt()?->format(\DateTime::ATOM),
            ]
        ];
    }

    /**
     * Construit le payload pour l'événement quiz_completed
     */
    private function buildQuizCompletedPayload(QuizResult $quizResult): array
    {
        $user = $quizResult->getUser();
        $quiz = $quizResult->getQuiz();

        return [
            'event' => 'quiz_completed',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTime::ATOM),
            'data' => [
                'quiz_result_id' => $quizResult->getId(),
                'student' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName() ?? ($user->getName() . ' ' . $user->getLastname()),
                ],
                'quiz' => [
                    'id' => $quiz->getId(),
                    'title' => $quiz->getTitle(),
                    'totalPoints' => $quiz->getTotalPoints(),
                ],
                'course' => [
                    'id' => $quiz->getCours()?->getId(),
                    'title' => $quiz->getCours()?->getTitle(),
                ],
                'result' => [
                    'score' => $quizResult->getScore(),
                    'passed' => $quizResult->isPassed(),
                    'startedAt' => $quizResult->getStartedAt()?->format(\DateTime::ATOM),
                    'completedAt' => $quizResult->getCompletedAt()?->format(\DateTime::ATOM),
                ],
            ]
        ];
    }
}
