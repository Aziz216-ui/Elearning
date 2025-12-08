<?php

namespace App\Controller\Api;

use App\Entity\WebhookSubscription;
use App\Repository\WebhookSubscriptionRepository;
use App\Service\WebhookDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/webhooks', name: 'api_webhook_')]
class WebhookApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(WebhookSubscriptionRepository $webhookRepository): JsonResponse
    {
        // Seuls les administrateurs et gestionnaires de cours peuvent accéder
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        // Vérifier si c'est un admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'error' => 'You do not have permission to access webhooks'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer uniquement les webhooks de l'admin connecté
        $webhooks = $webhookRepository->findBy(['admin' => $user]);

        $data = array_map(function(WebhookSubscription $webhook) {
            return [
                'id' => $webhook->getId(),
                'url' => $webhook->getUrl(),
                'eventType' => $webhook->getEventType(),
                'isActive' => $webhook->isActive(),
                'createdAt' => $webhook->getCreatedAt()?->format(\DateTime::ATOM),
                'lastTriggeredAt' => $webhook->getLastTriggeredAt()?->format(\DateTime::ATOM),
                'failureCount' => $webhook->getFailureCount(),
            ];
        }, $webhooks);

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        // Vérifier les permissions
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'error' => 'You do not have permission to create webhooks'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Valider les données
        if (empty($data['url'])) {
            return new JsonResponse(['success' => false, 'error' => 'URL is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid URL format'], Response::HTTP_BAD_REQUEST);
        }

        $eventType = $data['eventType'] ?? 'quiz_started';
        if (!in_array($eventType, ['quiz_started', 'quiz_completed', 'all'])) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid event type'], Response::HTTP_BAD_REQUEST);
        }

        // Créer le webhook
        $webhook = new WebhookSubscription();
        $webhook->setAdmin($user);
        $webhook->setUrl($data['url']);
        $webhook->setEventType($eventType);

        $em->persist($webhook);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Webhook created successfully',
            'data' => [
                'id' => $webhook->getId(),
                'url' => $webhook->getUrl(),
                'eventType' => $webhook->getEventType(),
                'secret' => $webhook->getSecret(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(WebhookSubscription $webhook): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        // Vérifier les permissions
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'error' => 'You do not have permission'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que c'est le webhook de l'admin
        if ($webhook->getAdmin()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $webhook->getId(),
                'url' => $webhook->getUrl(),
                'eventType' => $webhook->getEventType(),
                'isActive' => $webhook->isActive(),
                'createdAt' => $webhook->getCreatedAt()?->format(\DateTime::ATOM),
                'lastTriggeredAt' => $webhook->getLastTriggeredAt()?->format(\DateTime::ATOM),
                'failureCount' => $webhook->getFailureCount(),
                'secret' => $webhook->getSecret(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(WebhookSubscription $webhook, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'error' => 'You do not have permission'], Response::HTTP_FORBIDDEN);
        }

        if ($webhook->getAdmin()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['url'])) {
            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid URL format'], Response::HTTP_BAD_REQUEST);
            }
            $webhook->setUrl($data['url']);
        }

        if (isset($data['eventType'])) {
            if (!in_array($data['eventType'], ['quiz_started', 'quiz_completed', 'all'])) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid event type'], Response::HTTP_BAD_REQUEST);
            }
            $webhook->setEventType($data['eventType']);
        }

        if (isset($data['isActive'])) {
            $webhook->setIsActive((bool) $data['isActive']);
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Webhook updated successfully',
            'data' => [
                'id' => $webhook->getId(),
                'url' => $webhook->getUrl(),
                'eventType' => $webhook->getEventType(),
                'isActive' => $webhook->isActive(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(WebhookSubscription $webhook, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'error' => 'You do not have permission'], Response::HTTP_FORBIDDEN);
        }

        if ($webhook->getAdmin()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($webhook);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Webhook deleted successfully']);
    }

    #[Route('/{id}/test', name: 'test', methods: ['POST'])]
    public function test(WebhookSubscription $webhook, Request $request, EntityManagerInterface $em, WebhookDispatcher $dispatcher): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'error' => 'You do not have permission'], Response::HTTP_FORBIDDEN);
        }

        if ($webhook->getAdmin()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
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

        // Envoyer via le WebhookDispatcher
        $success = $dispatcher->dispatch($webhook, $testPayload);

        return new JsonResponse([
            'success' => $success,
            'message' => $success ? 'Test webhook sent successfully' : 'Failed to send test webhook',
        ]);
    }
}
