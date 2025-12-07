<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\QuizNotificationService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/debug')]
class DebugNotificationController extends AbstractController
{
    #[Route('/test-notification', name: 'debug_test_notification')]
    public function testNotification(
        NotificationService $notificationService,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        // Créer une notification test
        $notification = $notificationService->createNotification(
            $user,
            'Test de notification',
            'Ceci est une notification de test pour vérifier le système',
            'info',
            ['test' => true, 'timestamp' => time()]
        );

        return new Response('Notification test créée! ID: ' . $notification->getId());
    }

    #[Route('/check-webhooks', name: 'debug_check_webhooks')]
    public function checkWebhooks(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        $webhooks = $em->getRepository('App\Entity\WebhookSubscription')->findBy(['admin' => $user]);

        $output = "<h1>Webhooks pour l'utilisateur: " . $user->getEmail() . "</h1>";
        $output .= "<ul>";
        
        foreach ($webhooks as $webhook) {
            $output .= "<li>";
            $output .= "ID: " . $webhook->getId() . " | ";
            $output .= "URL: " . $webhook->getUrl() . " | ";
            $output .= "Event: " . $webhook->getEventType() . " | ";
            $output .= "Active: " . ($webhook->isActive() ? 'Oui' : 'Non');
            $output .= "</li>";
        }
        
        $output .= "</ul>";
        $output .= "<p>Total: " . count($webhooks) . " webhooks</p>";

        return new Response($output);
    }
}
