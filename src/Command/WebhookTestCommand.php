<?php

namespace App\Command;

use App\Entity\WebhookSubscription;
use App\Repository\WebhookSubscriptionRepository;
use App\Service\WebhookDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'webhook:test',
    description: 'Test a webhook by sending a sample payload',
)]
class WebhookTestCommand extends Command
{
    public function __construct(
        private WebhookSubscriptionRepository $webhookRepository,
        private WebhookDispatcher $dispatcher,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('webhook-id', InputArgument::REQUIRED, 'The webhook ID to test')
            ->setDescription('Send a test webhook to verify the endpoint is working');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $webhookId = $input->getArgument('webhook-id');

        // Récupérer le webhook
        $webhook = $this->webhookRepository->find($webhookId);

        if (!$webhook) {
            $io->error(sprintf('Webhook with ID %d not found', $webhookId));
            return Command::FAILURE;
        }

        $io->section('Testing Webhook');
        $io->text(sprintf('Webhook ID: %d', $webhook->getId()));
        $io->text(sprintf('URL: %s', $webhook->getUrl()));
        $io->text(sprintf('Event Type: %s', $webhook->getEventType()));
        $io->text(sprintf('Status: %s', $webhook->isActive() ? 'Active' : 'Inactive'));

        if (!$webhook->isActive()) {
            $io->warning('This webhook is inactive. Enabling temporarily for testing...');
            $webhook->setIsActive(true);
            $this->em->flush();
        }

        // Créer un payload de test
        $testPayload = [
            'event' => 'quiz_started',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTime::ATOM),
            'data' => [
                'quiz_result_id' => 0,
                'student' => [
                    'id' => 0,
                    'email' => 'test@example.com',
                    'fullName' => 'Test Student',
                ],
                'quiz' => [
                    'id' => 0,
                    'title' => 'Test Quiz',
                    'description' => 'This is a test webhook payload',
                    'totalPoints' => 100,
                    'timeLimit' => 1800,
                ],
                'course' => [
                    'id' => 0,
                    'title' => 'Test Course',
                ],
                'startedAt' => (new \DateTimeImmutable())->format(\DateTime::ATOM),
            ]
        ];

        $io->newLine();
        $io->section('Sending Test Payload');

        // Envoyer le webhook
        $success = $this->dispatcher->dispatch($webhook, $testPayload);

        if ($success) {
            $io->success('✅ Webhook test successful!');
            $io->text('The webhook endpoint received and accepted the test payload.');
            return Command::SUCCESS;
        } else {
            $io->error('❌ Webhook test failed!');
            $io->text('The webhook endpoint did not respond successfully.');
            $io->text(sprintf('Failure count: %d', $webhook->getFailureCount()));
            return Command::FAILURE;
        }
    }
}
