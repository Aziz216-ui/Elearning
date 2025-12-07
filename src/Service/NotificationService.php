<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationRepository $notificationRepository
    ) {
    }

    public function createNotification(User $admin, string $title, string $message, string $type = 'info', array $data = null): Notification
    {
        $notification = new Notification();
        $notification->setAdmin($admin);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setType($type);
        $notification->setData($data);

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    public function notifyQuizStarted(User $admin, array $quizData): Notification
    {
        return $this->createNotification(
            $admin,
            'Quiz commencé',
            sprintf('Un étudiant a commencé le quiz "%s"', $quizData['quiz']['title']),
            'quiz_started',
            $quizData
        );
    }

    public function notifyQuizCompleted(User $admin, array $quizData): Notification
    {
        return $this->createNotification(
            $admin,
            'Quiz terminé',
            sprintf('Un étudiant a terminé le quiz "%s" avec un score de %d%%', $quizData['quiz']['title'], $quizData['result']['score'] ?? 0),
            'quiz_completed',
            $quizData
        );
    }

    public function getUnreadNotifications(User $admin): array
    {
        return $this->notificationRepository->findUnreadByAdmin($admin);
    }

    public function getUnreadCount(User $admin): int
    {
        return $this->notificationRepository->getUnreadCount($admin);
    }

    public function markAsRead(int $notificationId): void
    {
        $this->notificationRepository->markAsRead($notificationId);
    }

    public function markAllAsRead(User $admin): void
    {
        $this->notificationRepository->markAllAsReadForAdmin($admin);
    }
}
