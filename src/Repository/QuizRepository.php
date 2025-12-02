<?php

namespace App\Repository;

use App\Entity\Quiz;
use App\Entity\Cours;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    /**
     * Recherche les quiz dont le titre contient le mot-clé fourni.
     *
     * @param string $keyword
     * @return Quiz[]
     */
    public function searchByTitle(string $keyword): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.title LIKE :keyword')
            ->setParameter('keyword', '%'.$keyword.'%')
            ->orderBy('q.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des questions dans un quiz spécifique par mot-clé
     *
     * @param int $quizId L'ID du quiz dans lequel effectuer la recherche
     * @param string $searchTerm Le terme à rechercher dans les questions
     * @return array Les questions correspondantes
     */
    public function findQuestionsBySearchTerm(int $quizId, string $searchTerm): array
    {
        return $this->createQueryBuilder('q')
            ->select('question')
            ->join('q.questions', 'question')
            ->where('q.id = :quizId')
            ->andWhere('LOWER(question.text) LIKE LOWER(:searchTerm)')
            ->setParameter('quizId', $quizId)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('question.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des quiz selon plusieurs critères
     *
     * @param string|null $title Terme de recherche pour le titre
     * @param Cours|null $cours Filtre par cours
     * @param string|bool|null $isVisible Filtre par visibilité (true/1 = visible, false/0 = masqué, null = pas de filtre)
     * @return Quiz[]
     */
    public function searchByCriteria(?string $title, ?Cours $cours, $isVisible): array
    {
        $qb = $this->createQueryBuilder('q');

        if ($title) {
            $qb->andWhere('q.title LIKE :title')
               ->setParameter('title', '%'.$title.'%');
        }

        // Handle visibility filter
        if ($isVisible !== null) {
            $visibility = $isVisible;
            if (is_string($isVisible)) {
                $visibility = $isVisible === '1' ? true : ($isVisible === '0' ? false : null);
            }
            
            if ($visibility !== null) {
                $qb->andWhere('q.is_visible = :isVisible')
                   ->setParameter('isVisible', $visibility);
            }
        }

        // Filtrage par cours si nécessaire
        if ($cours) {
            $qb->andWhere('q.cours = :cours')
               ->setParameter('cours', $cours);
        }

        return $qb->getQuery()->getResult();
    }


    /**
     * ✅ NOUVELLE FONCTION - Recherche avancée avec tous les filtres
     *
     * @param string|null $titre Terme de recherche pour le titre
     * @param string|null $visibilite 'tous', 'visible' ou 'masque'
     * @param int|null $coursId ID du cours pour le filtrage
     * @return Quiz[]
     */
    public function rechercheAvancee(?string $titre = null, ?string $visibilite = null, ?int $coursId = null): array
    {
        $qb = $this->createQueryBuilder('q')
            ->leftJoin('q.cours', 'c')
            ->addSelect('c')
            ->leftJoin('q.questions', 'questions')
            ->addSelect('questions');
        
        // Filtre par titre
        if ($titre) {
            $qb->andWhere('LOWER(q.title) LIKE LOWER(:titre)')
               ->setParameter('titre', '%' . $titre . '%');
        }
        
        // Filtre par visibilité
        if ($visibilite !== null && $visibilite !== 'tous') {
            $visible = ($visibilite === 'visible') ? true : false;
            $qb->andWhere('q.is_visible = :visible')
               ->setParameter('visible', $visible);
        }
        
        // Filtre par cours
        if ($coursId) {
            $qb->andWhere('q.cours = :cours')
               ->setParameter('cours', $coursId);
        }
        
        return $qb->orderBy('q.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * ✅ NOUVELLE FONCTION - Récupère les statistiques des quiz
     *
     * @return array Tableau contenant les statistiques (total, visibles, masqués, pourcentage)
     */
    public function getStatistiques(): array
    {
        $total = $this->count([]);
        $visibles = $this->count(['is_visible' => true]);
        $masques = $this->count(['is_visible' => false]);
        
        return [
            'total' => $total,
            'visibles' => $visibles,
            'masques' => $masques,
            'pourcentageVisible' => $total > 0 ? round(($visibles / $total) * 100) : 0,
            'pourcentageMasque' => $total > 0 ? round(($masques / $total) * 100) : 0
        ];
    }

    /**
     * ✅ NOUVELLE FONCTION - Récupère les statistiques détaillées par cours
     *
     * @return array Statistiques groupées par cours
     */
    public function getStatistiquesParCours(): array
    {
        return $this->createQueryBuilder('q')
            ->select('c.nom as coursNom, COUNT(q.id) as nbQuiz, SUM(CASE WHEN q.is_visible = true THEN 1 ELSE 0 END) as nbVisibles')
            ->leftJoin('q.cours', 'c')
            ->groupBy('c.id')
            ->orderBy('nbQuiz', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ NOUVELLE FONCTION - Filtre par nombre de questions
     *
     * @param int $min Nombre minimum de questions
     * @param int $max Nombre maximum de questions
     * @return Quiz[]
     */
    public function findByNombreQuestions(int $min, int $max): array
    {
        return $this->createQueryBuilder('q')
            ->select('q')
            ->leftJoin('q.questions', 'questions')
            ->groupBy('q.id')
            ->having('COUNT(questions.id) BETWEEN :min AND :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max)
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ NOUVELLE FONCTION - Filtre par plage de points
     *
     * @param int $minPoints Points minimum
     * @param int $maxPoints Points maximum
     * @return Quiz[]
     */
    public function findByPointsRange(int $minPoints, int $maxPoints): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.totalPoints BETWEEN :minPoints AND :maxPoints')
            ->setParameter('minPoints', $minPoints)
            ->setParameter('maxPoints', $maxPoints)
            ->orderBy('q.totalPoints', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ NOUVELLE FONCTION - Tri personnalisé
     *
     * @param string $orderBy Champ de tri (title, totalPoints, timeLimit, is_visible, id)
     * @param string $direction Direction du tri (ASC ou DESC)
     * @return Quiz[]
     */
    public function findAllOrdered(string $orderBy = 'title', string $direction = 'ASC'): array
    {
        $validFields = ['title', 'totalPoints', 'timeLimit', 'is_visible', 'id'];
        $validDirections = ['ASC', 'DESC'];

        if (!in_array($orderBy, $validFields, true)) {
            $orderBy = 'title';
        }

        if (!in_array($direction, $validDirections, true)) {
            $direction = 'ASC';
        }

        return $this->createQueryBuilder('q')
            ->leftJoin('q.cours', 'c')
            ->addSelect('c')
            ->orderBy('q.' . $orderBy, $direction)
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ NOUVELLE FONCTION - Récupère les quiz récents
     *
     * @param int $limit Nombre de quiz à récupérer
     * @return Quiz[]
     */
    public function findRecent(int $limit = 5): array
    {
        // Pas de createdAt dans l'entité, on utilise l'id comme approximation
        return $this->createQueryBuilder('q')
            ->orderBy('q.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ NOUVELLE FONCTION - Compte les quiz par visibilité
     *
     * @return array ['visible' => count, 'masque' => count]
     */
    public function countByVisibilite(): array
    {
        $result = $this->createQueryBuilder('q')
            ->select('q.is_visible, COUNT(q.id) as total')
            ->groupBy('q.is_visible')
            ->getQuery()
            ->getResult();

        $counts = ['visible' => 0, 'masque' => 0];

        foreach ($result as $row) {
            if ($row['is_visible']) {
                $counts['visible'] = $row['total'];
            } else {
                $counts['masque'] = $row['total'];
            }
        }

        return $counts;
    }

    /**
     * Trouve tous les quiz avec options de tri et filtrage
     *
     * @param array $filters
     * @return Quiz[]
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('q');

        // Filtrer par cours
        if (isset($filters['coursId'])) {
            $qb->andWhere('q.cours = :coursId')
               ->setParameter('coursId', $filters['coursId']);
        }

        // Filtrer par visibilité (champ is_visible dans l'entité)
        if (isset($filters['visible'])) {
            $qb->andWhere('q.is_visible = :visible')
               ->setParameter('visible', $filters['visible']);
        }

        // Recherche par titre ou description
        if (!empty($filters['search'] ?? null)) {
            $qb->andWhere('q.title LIKE :search OR q.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Tri
        $sortBy = $filters['sortBy'] ?? 'date';
        $sortOrder = strtoupper($filters['sortOrder'] ?? 'DESC');

        switch ($sortBy) {
            case 'points':
                // Utiliser totalPoints comme champ de points agrégés
                $qb->orderBy('q.totalPoints', $sortOrder);
                break;
            case 'questions':
                // Tri par nombre de questions (join + groupBy + COUNT)
                $qb->leftJoin('q.questions', 'ques')
                   ->addSelect('COUNT(ques.id) AS HIDDEN nbQuestions')
                   ->groupBy('q.id')
                   ->orderBy('nbQuestions', $sortOrder);
                break;
            case 'duration':
                // Utiliser timeLimit comme durée
                $qb->orderBy('q.timeLimit', $sortOrder);
                break;
            case 'title':
                $qb->orderBy('q.title', $sortOrder);
                break;
            case 'date':
            default:
                // Pas de createdAt dans l'entité : on se rabat sur l'id comme proxy
                $qb->orderBy('q.id', $sortOrder);
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les quiz triés par points (croissant)
     *
     * @param int|null $coursId
     * @return Quiz[]
     */
    public function findOrderedByPointsAsc(?int $coursId = null): array
    {
        $qb = $this->createQueryBuilder('q')
            ->orderBy('q.totalPoints', 'ASC');

        if ($coursId !== null) {
            $qb->andWhere('q.cours = :coursId')
               ->setParameter('coursId', $coursId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les quiz triés par points (décroissant)
     *
     * @param int|null $coursId
     * @return Quiz[]
     */
    public function findOrderedByPointsDesc(?int $coursId = null): array
    {
        $qb = $this->createQueryBuilder('q')
            ->orderBy('q.totalPoints', 'DESC');

        if ($coursId !== null) {
            $qb->andWhere('q.cours = :coursId')
               ->setParameter('coursId', $coursId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les quiz par cours avec tri par points
     */
    public function findByCoursOrderedByPoints(int $coursId, string $sortOrder = 'ASC'): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.cours = :coursId')
            ->setParameter('coursId', $coursId)
            ->orderBy('q.totalPoints', strtoupper($sortOrder))
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les quiz visibles par cours triés par points
     */
    public function findVisibleByCoursOrderedByPoints(int $coursId, string $sortOrder = 'ASC'): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.cours = :coursId')
            ->andWhere('q.is_visible = :visible')
            ->setParameter('coursId', $coursId)
            ->setParameter('visible', true)
            ->orderBy('q.totalPoints', strtoupper($sortOrder))
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des quiz par titre avec tri par points
     */
    public function searchByTitleOrderedByPoints(string $searchTerm, string $sortOrder = 'ASC'): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.title LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('q.totalPoints', strtoupper($sortOrder))
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de points pour un cours
     */
    public function getTotalPointsByCours(int $coursId): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('SUM(q.totalPoints)')
            ->andWhere('q.cours = :coursId')
            ->setParameter('coursId', $coursId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les quiz avec un nombre de points spécifique
     */
    public function findByPoints(int $points): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.totalPoints = :points')
            ->setParameter('points', $points)
            ->orderBy('q.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtient les statistiques des quiz d'un cours
     */
    public function getQuizStatsByCours(int $coursId): array
    {
        $result = $this->createQueryBuilder('q')
            ->leftJoin('q.questions', 'ques')
            ->select([
                'COUNT(DISTINCT q.id) as totalQuizzes',
                'SUM(q.totalPoints) as totalPoints',
                'AVG(q.totalPoints) as avgPoints',
                'MAX(q.totalPoints) as maxPoints',
                'MIN(q.totalPoints) as minPoints',
                'COUNT(ques.id) as totalQuestions',
                'SUM(q.timeLimit) as totalDuration',
            ])
            ->andWhere('q.cours = :coursId')
            ->setParameter('coursId', $coursId)
            ->getQuery()
            ->getSingleResult();

        return [
            'totalQuizzes'   => (int) ($result['totalQuizzes'] ?? 0),
            'totalPoints'    => (int) ($result['totalPoints'] ?? 0),
            'avgPoints'      => isset($result['avgPoints']) ? round((float) $result['avgPoints'], 2) : 0.0,
            'maxPoints'      => (int) ($result['maxPoints'] ?? 0),
            'minPoints'      => (int) ($result['minPoints'] ?? 0),
            'totalQuestions' => (int) ($result['totalQuestions'] ?? 0),
            'totalDuration'  => (int) ($result['totalDuration'] ?? 0),
        ];
    }
}