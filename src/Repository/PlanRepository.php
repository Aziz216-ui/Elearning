<?php

namespace App\Repository;

use App\Entity\Plan;
use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plan>
 */
class PlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plan::class);
    }

    /**
     * @return Subscription[] Returns subscriptions for a given plan using a join
     */
    public function findSubscriptionsByPlan(Plan $plan): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('s')
            ->from(Subscription::class, 's')
            ->where('s.plan = :plan')
            ->setParameter('plan', $plan)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Plan[] Returns all inactive plans ordered by price ascending
     */
    public function findInactivePlans(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', false)
            ->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Plan[] Returns all active plans ordered by price ascending
     */
    public function findActivePlans(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Plan[] Alias for findActivePlans, kept for clearer name 'findAllByPrice' (active only)
     */
    public function findAllActiveByPrice(): array
    {
        return $this->findActivePlans();
    }

  
    /**
     * @return Plan[] Returns plans filtered by optional min/max price
     */
    public function findByPriceRange(?string $minPrice, ?string $maxPrice): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($minPrice !== null) {
            $qb->andWhere('p.price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        return $qb
            ->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Plan[] Returns an array of Plan objects
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

    //    public function findOneBySomeField($value): ?Plan
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
