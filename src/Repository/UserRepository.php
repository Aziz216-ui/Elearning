<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    // src/Repository/UserRepository.php

    public function countUsersByMonth(int $year): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT 
            DATE_FORMAT(created_at, "%b") AS month,
            COUNT(*) AS count
        FROM user
        WHERE YEAR(created_at) = :year
        GROUP BY month
        ORDER BY MONTH(created_at)
    ';

        // Utiliser executeQuery qui retourne un Result avec fetchAllAssociative()
        $result = $conn->executeQuery($sql, ['year' => $year]);

        // Résultat sous forme de tableau associatif
        return $result->fetchAllAssociative();
    }



    public function countUserBySexe(?string $sexe = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Normalise les valeurs NULL pour afficher "Non renseigné"
        if ($sexe !== null) {
            $sql = 'SELECT COALESCE(sexe, "Non renseigné") AS sexe, COUNT(*) AS count
                    FROM `user`
                    WHERE sexe = :sexe
                    GROUP BY sexe';

            $result = $conn->executeQuery($sql, ['sexe' => $sexe]);
        } else {
            $sql = 'SELECT COALESCE(sexe, "Non renseigné") AS sexe, COUNT(*) AS count
                    FROM `user`
                    GROUP BY sexe
                    ORDER BY count DESC';

            $result = $conn->executeQuery($sql);
        }

        return $result->fetchAllAssociative();
    }

    // src/Repository/UserRepository.php

    public function countUsersByBirthYear(): array
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        // Récupère le nom réel de la table et de la colonne mappée à birthdate
        $meta = $em->getClassMetadata(User::class);
        if (! $meta->hasField('birthdate')) {
            // si pas de birthdate, retourner tableau vide
            return [];
        }

        $tableName = $meta->getTableName();
        $colName = $meta->getColumnName('birthdate');

        $platform = strtolower($conn->getDatabasePlatform()->getName());

        if (strpos($platform, 'mysql') !== false) {
            $yearExpr = "YEAR($tableName.$colName)";
        } elseif (strpos($platform, 'postgres') !== false || strpos($platform, 'postgresql') !== false) {
            $yearExpr = "EXTRACT(YEAR FROM $tableName.$colName)";
        } else {
            // fallback: use SQL function YEAR (may fail on some DBs)
            $yearExpr = "YEAR($tableName.$colName)";
        }

        $sql = sprintf(
            'SELECT %s AS year, COUNT(*) AS count FROM %s WHERE %s IS NOT NULL GROUP BY year ORDER BY year ASC',
            $yearExpr,
            $conn->quoteIdentifier($tableName),
            $conn->quoteIdentifier($colName)
        );

        $result = $conn->executeQuery($sql);
        $rows = $result->fetchAllAssociative();

        // Normaliser le format (casting en int pour count)
        return array_map(function(array $r) {
            return ['year' => (int) $r['year'], 'count' => (int) $r['count']];
        }, $rows);
    }

    // src/Repository/UserRepository.php

    public function findUserByName(string $search): array
    {
        $qb = $this->createQueryBuilder('u');

        // Utiliser l'ExpressionBuilder pour éviter les erreurs de parsing DQL
        $expr = $qb->expr();
        $like = $expr->orX(
            $expr->like('LOWER(u.name)', ':search'),
            $expr->like('LOWER(u.lastname)', ':search'),
            $expr->like("LOWER(CONCAT(u.name, ' ', u.lastname))", ':search'),
            $expr->like("LOWER(CONCAT(u.lastname, ' ', u.name))", ':search'),
            $expr->like('LOWER(u.email)', ':search')
        );

        $qb->where($like)
            ->setParameter('search', '%' . mb_strtolower($search) . '%')
            ->orderBy('u.lastname', 'ASC');

        return $qb->getQuery()->getResult();
    }






    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
