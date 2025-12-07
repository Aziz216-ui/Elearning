<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * @return Subscription[] Returns subscriptions for a user ordered by startDate DESC
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[] Returns subscriptions for a given plan
     */
    public function findByPlan(Plan $plan): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.plan = :plan')
            ->setParameter('plan', $plan)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[] Returns subscriptions filtered only by auto-renew flag
     */
    public function findByAutoRenew(bool $autoRenew): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.autoRenew = :autoRenew')
            ->setParameter('autoRenew', $autoRenew)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[] Returns active subscriptions for a user
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[] Returns inactive subscriptions for a user
     */
    public function findInactiveByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'canceled')
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[] Returns subscriptions filtered by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[] Returns active subscriptions with auto-renew enabled for a user
     */
    public function findActiveAutoRenewingByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.status = :status')
            ->andWhere('s.autoRenew = :autoRenew')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->setParameter('autoRenew', true)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Subscription[] Returns an array of Subscription objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Subscription
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
