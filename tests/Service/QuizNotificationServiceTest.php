<?php

namespace App\Tests\Service;

use App\Entity\Quiz;
use App\Entity\QuizResult;
use App\Entity\User;
use App\Entity\WebhookSubscription;
use App\Repository\WebhookSubscriptionRepository;
use App\Service\QuizNotificationService;
use App\Service\WebhookDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class QuizNotificationServiceTest extends TestCase
{
    private QuizNotificationService $service;
    private WebhookDispatcher $dispatcher;
    private WebhookSubscriptionRepository $webhookRepository;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(WebhookDispatcher::class);
        $this->webhookRepository = $this->createMock(WebhookSubscriptionRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new QuizNotificationService(
            $this->dispatcher,
            $this->webhookRepository,
            $this->em,
            $this->logger,
        );
    }

    public function testNotifyQuizStarted(): void
    {
        // Créer les objets de test
        $user = new User();
        $user->setEmail('student@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $quiz = new Quiz();
        $quiz->setTitle('Math Quiz');
        $quiz->setDescription('Basic Mathematics');
        $quiz->setTotalPoints(100);
        $quiz->setTimeLimit(1800);

        $quizResult = new QuizResult();
        $quizResult->setUser($user);
        $quizResult->setQuiz($quiz);
        $quizResult->setStartedAt(new \DateTimeImmutable());

        // Mock des webhooks
        $webhook = $this->createMock(WebhookSubscription::class);
        $this->webhookRepository
            ->expects($this->once())
            ->method('findActiveWebhooksForEvent')
            ->with('quiz_started')
            ->willReturn([$webhook]);

        // Mock du dispatcher
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch');

        // Exécuter la méthode
        $this->service->notifyQuizStarted($quizResult);

        // Les assertions sont implicites via les mocks
        $this->assertTrue(true);
    }

    public function testNotifyQuizCompleted(): void
    {
        // Créer les objets de test
        $user = new User();
        $user->setEmail('student@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $quiz = new Quiz();
        $quiz->setTitle('Math Quiz');
        $quiz->setTotalPoints(100);

        $quizResult = new QuizResult();
        $quizResult->setUser($user);
        $quizResult->setQuiz($quiz);
        $quizResult->setScore(85);
        $quizResult->setStartedAt(new \DateTimeImmutable());
        $quizResult->setCompletedAt(new \DateTimeImmutable());
        $quizResult->setPassed(true);

        // Mock des webhooks
        $webhook = $this->createMock(WebhookSubscription::class);
        $this->webhookRepository
            ->expects($this->once())
            ->method('findActiveWebhooksForEvent')
            ->with('quiz_completed')
            ->willReturn([$webhook]);

        // Mock du dispatcher
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch');

        // Exécuter la méthode
        $this->service->notifyQuizCompleted($quizResult);

        // Les assertions sont implicites via les mocks
        $this->assertTrue(true);
    }

    public function testNoWebhooksConfigured(): void
    {
        $user = new User();
        $quiz = new Quiz();
        $quiz->setTitle('Math Quiz');
        $quiz->setTotalPoints(100);

        $quizResult = new QuizResult();
        $quizResult->setUser($user);
        $quizResult->setQuiz($quiz);

        // Mock: aucun webhook
        $this->webhookRepository
            ->expects($this->once())
            ->method('findActiveWebhooksForEvent')
            ->willReturn([]);

        // Le dispatcher ne devrait pas être appelé
        $this->dispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->service->notifyQuizStarted($quizResult);

        $this->assertTrue(true);
    }
}
