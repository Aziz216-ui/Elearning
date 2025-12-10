<?php

namespace App\Repository;

use App\Entity\WebhookSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebhookSubscription>
 */
class WebhookSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebhookSubscription::class);
    }

    /**
     * Récupère tous les webhooks actifs pour un type d'événement spécifique
     */
    public function findActiveWebhooksForEvent(string $eventType): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.isActive = :isActive')
            ->andWhere('w.eventType IN (:eventType, :all)')
            ->setParameter('isActive', true)
            ->setParameter('eventType', $eventType)
            ->setParameter('all', 'all')
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les webhooks d'un admin spécifique
     */
    public function findByAdmin($admin): array
    {
        return $this->findBy(['admin' => $admin], ['createdAt' => 'DESC']);
    }

    /**
     * Récupère les webhooks actifs d'un admin
     */
    public function findActiveByAdmin($admin): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.admin = :admin')
            ->andWhere('w.isActive = :isActive')
            ->setParameter('admin', $admin)
            ->setParameter('isActive', true)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les webhooks avec trop d'échecs
     */
    public function findFailedWebhooks(int $failureThreshold = 10): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.failureCount >= :threshold')
            ->setParameter('threshold', $failureThreshold)
            ->orderBy('w.failureCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
