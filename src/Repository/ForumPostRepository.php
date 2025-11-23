<?php

namespace App\Repository;

use App\Entity\ForumPost;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ForumPost>
 */
class ForumPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumPost::class);
    }

    /**
     * Sauvegarde un post
     */
    public function save(ForumPost $post, bool $flush = false): void
    {
        $this->getEntityManager()->persist($post);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un post
     */
    public function remove(ForumPost $post, bool $flush = false): void
    {
        $this->getEntityManager()->remove($post);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Recherche par titre ou contenu
     */
    public function searchByTitleOrContent(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.titre LIKE :q OR p.contenu LIKE :q')
            ->andWhere('p.enabled = :enabled')
            ->setParameter('q', '%' . $query . '%')
            ->setParameter('enabled', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les posts activés
     */
    public function findEnabled(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('p.comments', 'co')
            ->addSelect('co')
            ->andWhere('p.enabled = :val')
            ->setParameter('val', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les posts par catégorie
     */
    public function findByCategory(Category $category): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('p.comments', 'c')
            ->addSelect('c')
            ->where('p.category = :category')
            ->andWhere('p.enabled = :enabled')
            ->setParameter('category', $category)
            ->setParameter('enabled', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les posts par slug de catégorie
     */
    public function findByCategorySlug(string $slug): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'cat')
            ->leftJoin('p.user', 'u')
            ->addSelect('cat', 'u')
            ->where('cat.slug = :slug')
            ->andWhere('p.enabled = :enabled')
            ->setParameter('slug', $slug)
            ->setParameter('enabled', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les posts les plus récents
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les posts les plus populaires (par vues)
     */
    public function findMostViewed(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('p.vues', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les posts les plus likés
     */
    public function findMostLiked(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('p.likes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les posts avec le plus de commentaires
     */
    public function findMostCommented(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('p.comments', 'co')
            ->where('p.enabled = :enabled')
            ->setParameter('enabled', true)
            ->groupBy('p.id')
            ->orderBy('COUNT(co.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée avec filtres
     */
    public function searchWithFilters(
        ?string $query = null,
        ?Category $category = null,
        ?string $orderBy = 'createdAt',
        string $order = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.enabled = :enabled')
            ->setParameter('enabled', true);

        if ($query) {
            $qb->andWhere('p.titre LIKE :query OR p.contenu LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($category) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        $qb->orderBy('p.' . $orderBy, $order);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de posts par catégorie
     */
    public function countByCategory(Category $category): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.category = :category')
            ->andWhere('p.enabled = :enabled')
            ->setParameter('category', $category)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les posts d'un utilisateur
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un post avec toutes ses relations (pour l'affichage détaillé)
     */
    public function findOneWithRelations(int $id): ?ForumPost
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('p.comments', 'co')
            ->addSelect('co')
            ->leftJoin('co.user', 'cu')
            ->addSelect('cu')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Statistiques globales
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as totalPosts')
            ->addSelect('SUM(p.vues) as totalViews')
            ->addSelect('SUM(p.likes) as totalLikes')
            ->where('p.enabled = :enabled')
            ->setParameter('enabled', true);

        return $qb->getQuery()->getSingleResult();
    }
    #[Route('/', name: 'app_forum_post_index')]
public function index(
    ForumPostRepository $forumPostRepository,
    CategoryRepository $categoryRepository,
    Request $request
): Response {
    $query = $request->query->get('q');
    $categorySlug = $request->query->get('category');
    
    if ($query) {
        $posts = $forumPostRepository->searchByTitleOrContent($query);
    } elseif ($categorySlug) {
        $posts = $forumPostRepository->findByCategorySlug($categorySlug);
    } else {
        $posts = $forumPostRepository->findEnabled();
    }
    
    return $this->render('forum_post/index.html.twig', [
        'forum_posts' => $posts,
        'categories' => $categoryRepository->findAllOrderedByName(),
    ]);
}
}