<?php

namespace App\Repository;

use App\Entity\Auteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Auteur>
 */
class AuteurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Auteur::class);
    }

    /**
     * Retourne le nombre de cours d'un auteur (par son id)
     */
    public function countCoursByAuteur(int $auteurId): int
    {
        $dql = 'SELECT COUNT(c.id) FROM App\\Entity\\Cours c WHERE c.auteur = :aid';
        return (int) $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('aid', $auteurId)
            ->getSingleScalarResult();
    }

    /**
     * Liste les auteurs avec le nombre de cours, trié par nbCours DESC
     * Retourne un tableau de lignes: ['auteur' => Auteur, 'nbCours' => int]
     */
    public function findAuteursWithCoursCount(): array
    {
        $dql = 'SELECT a AS auteur, COUNT(c.id) AS nbCours
                FROM App\\Entity\\Auteur a
                LEFT JOIN App\\Entity\\Cours c WITH c.auteur = a
                GROUP BY a.id
                ORDER BY nbCours DESC';

        return $this->getEntityManager()->createQuery($dql)->getResult();
    }

    /**
     * Récupère les cours d'un auteur (alias pratique si on ne veut pas appeler le CoursRepository)
     * Retourne un tableau d'entités Cours.
     */
    public function findCoursByAuteurId(int $auteurId, string $sort = 'ASC'): array
    {
        $dql = 'SELECT c FROM App\\Entity\\Cours c WHERE c.auteur = :aid ORDER BY c.price ' . (strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC');
        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('aid', $auteurId)
            ->getResult();
    }

    /**
     * Lister les auteurs par spécialité exacte
     * @return Auteur[]
     */
    public function listBySpecialite(string $specialite): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.specialite = :sp')
            ->setParameter('sp', $specialite)
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par mot-clé sur nom, prénom, email, spécialité, bio
     * @return Auteur[]
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.nom LIKE :kw OR a.prenom LIKE :kw OR a.email LIKE :kw OR a.specialite LIKE :kw OR a.bio LIKE :kw')
            ->setParameter('kw', '%' . $keyword . '%')
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Alias: nombre total de cours d’un auteur
     */
    public function totalCoursesForAuteur(int $auteurId): int
    {
        return $this->countCoursByAuteur($auteurId);
    }

    /**
     * Auteurs avec au moins X cours (tableau: ['auteur' => Auteur, 'nbCours' => int])
     */
    public function authorsWithAtLeast(int $minCourses): array
    {
        $dql = 'SELECT a AS auteur, COUNT(c.id) AS nbCours
                FROM App\\Entity\\Auteur a
                LEFT JOIN App\\Entity\\Cours c WITH c.auteur = a
                GROUP BY a.id
                HAVING COUNT(c.id) >= :min
                ORDER BY nbCours DESC, a.nom ASC';

        return $this->getEntityManager()->createQuery($dql)
            ->setParameter('min', $minCourses)
            ->getResult();
    }

    /**
     * Auteurs sans cours (entités Auteur)
     * @return Auteur[]
     */
    public function authorsWithoutCourses(): array
    {
        $dql = 'SELECT a FROM App\\Entity\\Auteur a
                LEFT JOIN App\\Entity\\Cours c WITH c.auteur = a
                WHERE c.id IS NULL
                ORDER BY a.nom ASC';
        return $this->getEntityManager()->createQuery($dql)->getResult();
    }

    /**
     * Top auteurs par nombre de cours (tableau: ['auteur' => Auteur, 'nbCours' => int])
     */
    public function topAuthors(int $limit = 5): array
    {
        $dql = 'SELECT a AS auteur, COUNT(c.id) AS nbCours
                FROM App\\Entity\\Auteur a
                LEFT JOIN App\\Entity\\Cours c WITH c.auteur = a
                GROUP BY a.id
                ORDER BY nbCours DESC, a.nom ASC';
        return $this->getEntityManager()->createQuery($dql)
            ->setMaxResults($limit)
            ->getResult();
    }

    /**
     * Vérifie si un auteur possède au moins 1 cours
     */
    public function hasCourses(int $auteurId): bool
    {
        $dql = 'SELECT COUNT(c.id) FROM App\\Entity\\Cours c WHERE c.auteur = :aid';
        $count = (int) $this->getEntityManager()->createQuery($dql)
            ->setParameter('aid', $auteurId)
            ->getSingleScalarResult();
        return $count > 0;
    }
}
