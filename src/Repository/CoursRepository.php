<?php

namespace App\Repository;

use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cours>
 */
class CoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cours::class);
    }

    /**
     * Trouve les cours par catégorie exacte (champ string Cours.category)
     * Tri par prix ASC/DESC
     *
     * @return Cours[]
     */
    public function findByCategory(string $category, string $sort = 'ASC'): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('c.price', strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre par prix min/max avec catégorie optionnelle
     *
     * @return Cours[]
     */
    public function findByPriceBetween(float $min, float $max, ?string $category = null, string $sort = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.price >= :min')
            ->andWhere('c.price <= :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max);

        if ($category !== null && $category !== '') {
            $qb->andWhere('c.category = :cat')->setParameter('cat', $category);
        }

        return $qb->orderBy('c.price', strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Récupère le min et le max des prix, éventuellement filtré par catégorie
     * Retourne un tableau associatif: ['minPrice' => float|null, 'maxPrice' => float|null]
     */
    public function getMinMaxPrice(?string $category = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('MIN(c.price) AS minPrice, MAX(c.price) AS maxPrice');

        if ($category !== null && $category !== '') {
            $qb->andWhere('c.category = :cat')->setParameter('cat', $category);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Méthode pratique unique pour filtrer par catégorie (string), prix min/max et tri.
     *
     * @return Cours[]
     */
    public function findByFilters(?string $category, ?float $min, ?float $max, string $sort = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($category !== null && $category !== '') {
            $qb->andWhere('c.category = :cat')->setParameter('cat', $category);
        }
        if ($min !== null) {
            $qb->andWhere('c.price >= :min')->setParameter('min', $min);
        }
        if ($max !== null) {
            $qb->andWhere('c.price <= :max')->setParameter('max', $max);
        }

        return $qb->orderBy('c.price', strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Liste tous les cours triés par prix.
     *
     * @return Cours[]
     */
    public function findAllOrderedByPrice(string $sort = 'ASC'): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.price', strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les cours d'un auteur par son id, triés par prix (utile pour vues auteur)
     *
     * @return Cours[]
     */
    public function findByAuteurId(int $auteurId, string $sort = 'ASC'): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.auteur = :aid')
            ->setParameter('aid', $auteurId)
            ->orderBy('c.price', strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 1) Cours publiés / non publiés
     * @return Cours[]
     */
    public function findPublished(bool $published = true): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isPublished = :pub')
            ->setParameter('pub', $published)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 2) Recherche mot-clé (titre + description)
     * @return Cours[]
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.title LIKE :kw OR c.description LIKE :kw')
            ->setParameter('kw', '%' . $keyword . '%')
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 3) Filtrer par durée (DateTimeImmutable min/max)
     * @return Cours[]
     */
    public function findByDurationRange(?\DateTimeImmutable $min, ?\DateTimeImmutable $max): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($min !== null) {
            $qb->andWhere('c.duration >= :dmin')->setParameter('dmin', $min);
        }
        if ($max !== null) {
            $qb->andWhere('c.duration <= :dmax')->setParameter('dmax', $max);
        }

        return $qb->orderBy('c.duration', 'ASC')->getQuery()->getResult();
    }

    /**
     * 4) Tri générique sécurisé par liste blanche
     * @return Cours[]
     */
    public function sortBy(string $field, string $order = 'ASC'): array
    {
        $allowed = ['title', 'price', 'duration', 'category', 'id'];
        if (!in_array($field, $allowed, true)) {
            throw new \InvalidArgumentException('Champ de tri non valide');
        }

        return $this->createQueryBuilder('c')
            ->orderBy('c.' . $field, strtoupper($order) === 'DESC' ? 'DESC' : 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 5) Top cours les plus chers / moins chers
     * @return Cours[]
     */
    public function findMostExpensive(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.price', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Cours[]
     */
    public function findCheapest(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.price', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 6) Compter les cours par catégorie
     * Retourne [[category => string|null, total => int], ...]
     */
    public function countByCategory(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.category AS category, COUNT(c.id) AS total')
            ->groupBy('c.category')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 7) Catégories distinctes (pour un dropdown)
     * Retourne un tableau de scalars: [['category' => 'X'], ...]
     */
    public function findDistinctCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT c.category AS category')
            ->orderBy('c.category', 'ASC')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * 8) Vérifier si un auteur a des cours publiés
     */
    public function hasPublishedCourses(int $auteurId): bool
    {
        $count = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.auteur = :aid')
            ->andWhere('c.isPublished = true')
            ->setParameter('aid', $auteurId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * 9) Derniers cours publiés
     * @return Cours[]
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isPublished = true')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Méthode avancée: combine catégorie, prix, mot-clé, publié, durée, tri
     * @return Cours[]
     */
    public function findAdvanced(
        ?string $category,
        ?float $minPrice,
        ?float $maxPrice,
        ?string $keyword,
        ?bool $published,
        ?\DateTimeImmutable $durationMin,
        ?\DateTimeImmutable $durationMax,
        ?string $sortField = 'price',
        string $sortOrder = 'ASC'
    ): array {
        $qb = $this->createQueryBuilder('c');

        if ($category !== null && $category !== '') {
            $qb->andWhere('c.category = :cat')->setParameter('cat', $category);
        }
        if ($minPrice !== null) {
            $qb->andWhere('c.price >= :pmin')->setParameter('pmin', $minPrice);
        }
        if ($maxPrice !== null) {
            $qb->andWhere('c.price <= :pmax')->setParameter('pmax', $maxPrice);
        }
        if ($keyword !== null && $keyword !== '') {
            $qb->andWhere('c.title LIKE :kw OR c.description LIKE :kw')->setParameter('kw', '%' . $keyword . '%');
        }
        if ($published !== null) {
            $qb->andWhere('c.isPublished = :pub')->setParameter('pub', $published);
        }
        if ($durationMin !== null) {
            $qb->andWhere('c.duration >= :dmin')->setParameter('dmin', $durationMin);
        }
        if ($durationMax !== null) {
            $qb->andWhere('c.duration <= :dmax')->setParameter('dmax', $durationMax);
        }

        $allowedSort = ['title', 'price', 'duration', 'category', 'id'];
        $sortField = in_array($sortField ?? '', $allowedSort, true) ? $sortField : 'price';
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $qb->orderBy('c.' . $sortField, $sortOrder);

        return $qb->getQuery()->getResult();
    }
}
