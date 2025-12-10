<?php

namespace App\Service;

use App\Entity\WebhookSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebhookDispatcher
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2; // secondes

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Envoie un webhook avec gestion des retries
     */
    public function dispatch(WebhookSubscription $webhook, array $payload, int $retryCount = 0): bool
    {
        if (!$webhook->isActive()) {
            $this->logger->warning('Webhook is not active', ['webhook_id' => $webhook->getId()]);
            return false;
        }

        try {
            $signature = $this->generateSignature($payload, $webhook->getSecret());

            $response = $this->httpClient->request('POST', $webhook->getUrl(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $payload['event'] ?? 'unknown',
                ],
                'json' => $payload,
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                // Succès
                $webhook->resetFailureCount();
                $webhook->setLastTriggeredAt(new \DateTimeImmutable());
                $this->em->flush();

                $this->logger->info('Webhook dispatched successfully', [
                    'webhook_id' => $webhook->getId(),
                    'url' => $webhook->getUrl(),
                    'status' => $statusCode
                ]);

                return true;
            } else {
                // Erreur HTTP
                return $this->handleFailure($webhook, $retryCount, 'HTTP Error: ' . $statusCode);
            }
        } catch (\Exception $e) {
            return $this->handleFailure($webhook, $retryCount, $e->getMessage());
        }
    }

    /**
     * Gère les erreurs d'envoi de webhook
     */
    private function handleFailure(WebhookSubscription $webhook, int $retryCount, string $errorMessage): bool
    {
        $webhook->incrementFailureCount();

        if ($retryCount < self::MAX_RETRIES) {
            $this->logger->warning('Webhook dispatch failed, retrying...', [
                'webhook_id' => $webhook->getId(),
                'retry_count' => $retryCount,
                'error' => $errorMessage
            ]);

            // Attendre avant de réessayer
            sleep(self::RETRY_DELAY);

            return $this->dispatch($webhook, [], $retryCount + 1);
        } else {
            // Trop d'échecs, désactiver le webhook
            if ($webhook->getFailureCount() >= 10) {
                $webhook->setIsActive(false);
                $this->logger->error('Webhook disabled due to excessive failures', [
                    'webhook_id' => $webhook->getId(),
                    'failure_count' => $webhook->getFailureCount()
                ]);
            }

            $this->em->flush();

            $this->logger->error('Webhook dispatch failed after retries', [
                'webhook_id' => $webhook->getId(),
                'url' => $webhook->getUrl(),
                'error' => $errorMessage
            ]);

            return false;
        }
    }

    /**
     * Génère une signature HMAC pour le webhook
     */
    private function generateSignature(array $payload, ?string $secret): string
    {
        $json = json_encode($payload);
        
        if (!$secret) {
            return '';
        }

        return 'sha256=' . hash_hmac('sha256', $json, $secret);
    }

    /**
     * Vérifie la signature d'un webhook reçu
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
