<?php

namespace App\Controller\Admin;

use App\Entity\WebhookSubscription;
use App\Repository\WebhookSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/webhooks', name: 'admin_webhook_')]
class WebhookAdminController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(WebhookSubscriptionRepository $webhookRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        $webhooks = $webhookRepository->findByAdmin($user);

        return $this->render('admin/webhooks/index.html.twig', [
            'webhooks' => $webhooks,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $webhook = new WebhookSubscription();
            $webhook->setAdmin($user);
            $webhook->setUrl($data['url']);
            $webhook->setEventType($data['eventType'] ?? 'quiz_started');

            $em->persist($webhook);
            $em->flush();

            $this->addFlash('success', 'Webhook créé avec succès!');
            return $this->redirectToRoute('admin_webhook_index');
        }

        return $this->render('admin/webhooks/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(WebhookSubscription $webhook, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        if ($webhook->getAdmin()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $webhook->setUrl($data['url']);
            $webhook->setEventType($data['eventType']);
            $webhook->setIsActive($data['isActive'] ?? false);

            $em->flush();

            $this->addFlash('success', 'Webhook mis à jour avec succès!');
            return $this->redirectToRoute('admin_webhook_index');
        }

        return $this->render('admin/webhooks/edit.html.twig', [
            'webhook' => $webhook,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(WebhookSubscription $webhook, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        if ($webhook->getAdmin()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($webhook);
        $em->flush();

        $this->addFlash('success', 'Webhook supprimé avec succès!');
        return $this->redirectToRoute('admin_webhook_index');
    }

    #[Route('/{id}/test', name: 'test', methods: ['POST'])]
    public function test(WebhookSubscription $webhook, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

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
        $dispatcher = $this->container->get('App\Service\WebhookDispatcher');
        $success = $dispatcher->dispatch($webhook, $testPayload);

        if ($success) {
            $this->addFlash('success', 'Webhook de test envoyé avec succès!');
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi du webhook de test.');
        }

        return $this->redirectToRoute('admin_webhook_index');
    }

    #[Route('/status', name: 'status')]
    public function status(WebhookSubscriptionRepository $webhookRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        $webhooks = $webhookRepository->findByAdmin($user);
        $failedWebhooks = $webhookRepository->findFailedWebhooks(10);

        return $this->render('admin/webhooks/status.html.twig', [
            'webhooks' => $webhooks,
            'failedWebhooks' => $failedWebhooks,
        ]);
    }
}
