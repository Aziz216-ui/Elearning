<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findUnreadByAdmin(User $admin): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.admin = :admin')
            ->andWhere('n.isRead = false')
            ->orderBy('n.createdAt', 'DESC')
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getResult();
    }

    public function findByAdmin(User $admin, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.admin = :admin')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getResult();
    }

    public function markAsRead(int $id): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', true)
            ->where('n.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function markAllAsReadForAdmin(User $admin): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', true)
            ->where('n.admin = :admin')
            ->setParameter('admin', $admin)
            ->getQuery()
            ->execute();
    }

    public function getUnreadCount(User $admin): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.admin = :admin')
            ->andWhere('n.isRead = false')
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
