<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * @return Payment[] Returns payments for a user ordered by createdAt DESC
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Payment[] Returns payments for a user filtered by status
     */
    public function findByUserAndStatus(User $user, string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Payment[] Returns payments between two dates (inclusive)
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.createdAt >= :from')
            ->andWhere('p.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Payment[] Returns payments filtered by optional min/max amount
     */
    public function findByAmountRange(?string $minAmount, ?string $maxAmount): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($minAmount !== null) {
            $qb->andWhere('p.amount >= :minAmount')
               ->setParameter('minAmount', $minAmount);
        }

        if ($maxAmount !== null) {
            $qb->andWhere('p.amount <= :maxAmount')
               ->setParameter('maxAmount', $maxAmount);
        }

        return $qb
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Payment[] Returns an array of Payment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Payment
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
