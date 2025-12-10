<?php

namespace App\Controller\Admin;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'admin_notifications')]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        $notifications = $notificationRepository->findByAdmin($user, 50);

        return $this->render('admin/notifications.html.twig', [
            'notifications' => $notifications
        ]);
    }
}
