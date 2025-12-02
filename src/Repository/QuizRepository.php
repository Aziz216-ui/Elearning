<?php

namespace App\Repository;

use App\Entity\Quiz;
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
     * @param string|null $searchTerm Terme de recherche pour le titre
     * @param int|null $courseId ID du cours pour le filtrage
     * @param bool|null $isVisible Filtre par visibilité (true = visible, false = masqué, null = pas de filtre)
     * @return Quiz[]
     */
// src/Repository/QuizRepository.php

public function searchByCriteria(?string $title, ?Cours $cours, ?bool $isVisible): array
{
    $qb = $this->createQueryBuilder('q');

    if ($title) {
        $qb->andWhere('q.title LIKE :title')
           ->setParameter('title', '%'.$title.'%');
    }

    if ($isVisible !== null) {
        $qb->andWhere('q.isVisible = :isVisible')
           ->setParameter('isVisible', $isVisible);
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
            $qb->andWhere('q.isVisible = :visible')
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
        $visibles = $this->count(['isVisible' => true]);
        $masques = $this->count(['isVisible' => false]);
        
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
            ->select('c.nom as coursNom, COUNT(q.id) as nbQuiz, SUM(CASE WHEN q.isVisible = true THEN 1 ELSE 0 END) as nbVisibles')
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
            ->where('q.points BETWEEN :min AND :max')
            ->setParameter('min', $minPoints)
            ->setParameter('max', $maxPoints)
            ->orderBy('q.points', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ NOUVELLE FONCTION - Tri personnalisé
     *
     * @param string $orderBy Champ de tri (title, points, createdAt, isVisible)
     * @param string $direction Direction du tri (ASC ou DESC)
     * @return Quiz[]
     */
    public function findAllOrdered(string $orderBy = 'title', string $direction = 'ASC'): array
    {
        $validFields = ['title', 'points', 'createdAt', 'isVisible'];
        $validDirections = ['ASC', 'DESC'];
        
        if (!in_array($orderBy, $validFields)) {
            $orderBy = 'title';
        }
        
        if (!in_array($direction, $validDirections)) {
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
        return $this->createQueryBuilder('q')
            ->orderBy('q.createdAt', 'DESC')
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
            ->select('q.isVisible, COUNT(q.id) as total')
            ->groupBy('q.isVisible')
            ->getQuery()
            ->getResult();
        
        $counts = ['visible' => 0, 'masque' => 0];
        
        foreach ($result as $row) {
            if ($row['isVisible']) {
                $counts['visible'] = $row['total'];
            } else {
                $counts['masque'] = $row['total'];
            }
        }
        
        return $counts;
    }
}