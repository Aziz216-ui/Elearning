<?php

namespace App\Repository;

use App\Entity\UserAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAnswer>
 */
class UserAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAnswer::class);
    }

    public function findUserAnswerForQuestion($user, $question)
    {
        return $this->createQueryBuilder('ua')
            ->andWhere('ua.user = :user')
            ->andWhere('ua.question = :question')
            ->setParameter('user', $user)
            ->setParameter('question', $question)
            ->orderBy('ua.answeredAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
