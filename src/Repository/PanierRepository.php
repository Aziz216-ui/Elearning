<?php

namespace App\Repository;

use App\Entity\Panier;
use App\Entity\User;
use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    /**
     * @return Panier[]
     */
    public function findUserPanier(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function isCourseInUserPanier(User $user, Cours $cours): bool
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->andWhere('p.cours = :cours')
            ->setParameter('user', $user)
            ->setParameter('cours', $cours)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
