<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notifications')]
class NotificationApiController extends AbstractController
{
    #[Route('/unread', name: 'api_notifications_unread', methods: ['GET'])]
    public function getUnreadNotifications(NotificationRepository $notificationRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $notifications = $notificationRepository->findUnreadByAdmin($user);
        $count = $notificationRepository->getUnreadCount($user);

        $data = array_map(function($notification) {
            return [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'type' => $notification->getType(),
                'createdAt' => $notification->getCreatedAt()->format('c'),
                'data' => $notification->getData()
            ];
        }, $notifications);

        return new JsonResponse([
            'notifications' => $data,
            'count' => $count
        ]);
    }

    #[Route('/mark-read/{id}', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markAsRead(int $id, NotificationRepository $notificationRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $notificationRepository->markAsRead($id);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/mark-all-read', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(NotificationRepository $notificationRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $notificationRepository->markAllAsReadForAdmin($user);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/recent', name: 'api_notifications_recent', methods: ['GET'])]
    public function getRecentNotifications(NotificationRepository $notificationRepository, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $limit = $request->query->get('limit', 10);
        $notifications = $notificationRepository->findByAdmin($user, $limit);

        $data = array_map(function($notification) {
            return [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'type' => $notification->getType(),
                'isRead' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()->format('c'),
                'data' => $notification->getData()
            ];
        }, $notifications);

        return new JsonResponse(['notifications' => $data]);
    }
}
