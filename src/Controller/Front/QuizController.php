<?php

namespace App\Controller\Front;

use App\Entity\QuizResult;
use App\Entity\Cours;
use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Answer;
use App\Entity\UserAnswer;
use App\Repository\PanierRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Contrôleur unifié pour la gestion des quiz côté front
 */
#[Route('/quiz')]
class QuizController extends AbstractController
{
    /**
     * Affiche la liste des quiz disponibles pour un cours
     */
    #[Route('/list/{id}', name: 'app_quiz_list')]
    public function list(Cours $cours, Request $request, PanierRepository $panierRepository, QuizRepository $quizRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        // Afficher la liste des quiz même si l'utilisateur n'a pas encore acheté ce cours.
        // Le contrôle d'accès strict est effectué lors du démarrage du quiz (action start).

        // Gestion du tri
        $sort = $request->query->get('sort');
        $orderBy = [];

        if ($sort === 'points_asc') {
            $orderBy = ['totalPoints' => 'ASC'];
        } elseif ($sort === 'points_desc') {
            $orderBy = ['totalPoints' => 'DESC'];
        }

        // Récupérer les quiz visibles pour ce cours
        $quizzes = $quizRepository->findBy(
            ['cours' => $cours, 'is_visible' => true],
            $orderBy
        );

        return $this->render('quiz/quiz_list.html.twig', [
            'cours' => $cours,
            'quizzes' => $quizzes,
            'currentSort' => $sort,
        ]);
    }

    /**
     * Affiche un quiz spécifique pour le passage
     */
    #[Route('/start/{id}', name: 'app_quiz_start')]
    public function start(Quiz $quiz, EntityManagerInterface $em, PanierRepository $panierRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();
        $cours = $quiz->getCours();
        
        // Vérifier les autorisations
        if (!$cours || !$panierRepository->isCourseInUserPanier($user, $cours)) {
            $this->addFlash('error', 'Accès non autorisé à ce quiz.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        if (!$quiz->isVisible()) {
            $this->addFlash('error', 'Ce quiz n\'est pas disponible pour le moment.');
            return $this->redirectToRoute('app_dashboard');
        }

        $questions = $em->getRepository(Question::class)->findBy(['quiz' => $quiz]);

        // La limite de temps est stockée en minutes dans le back-office, on la convertit en secondes pour le JS
        $timeLimitMinutes = $quiz->getTimeLimit() ?? 30; // 30 minutes par défaut si non défini
        $timeLimitSeconds = $timeLimitMinutes * 60;

        return $this->render('quiz/quiz.html.twig', [
            'cours' => $cours,
            'quiz' => $quiz,
            'questions' => $questions,
            'timeLimit' => $timeLimitSeconds,
        ]);
    }

    /**
     * Soumettre les réponses d'un quiz
     */
    #[Route('/submit/{id}', name: 'app_quiz_submit', methods: ['POST'])]
    public function submit(Quiz $quiz, Request $request, EntityManagerInterface $em, PanierRepository $panierRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();
        $cours = $quiz->getCours();

        // Vérifier les autorisations
        if (!$cours || !$panierRepository->isCourseInUserPanier($user, $cours)) {
            $this->addFlash('error', 'Accès non autorisé à ce quiz.');
            return $this->redirectToRoute('app_dashboard');
        }

        if (!$quiz->isVisible()) {
            $this->addFlash('error', 'Ce quiz n\'est pas disponible pour le moment.');
            return $this->redirectToRoute('app_dashboard');
        }

        $questions = $quiz->getQuestions();
        $score = 0;
        $totalPoints = 0;
        $correctAnswers = 0;
        $totalQuestions = $questions->count();

        $postData = $request->request->all();
        $answers = $postData['answers'] ?? [];

        // Instrumentation: vérifier la réception des données du formulaire
        $this->addFlash('info', sprintf('Soumission du quiz: %d réponse(s) reçue(s).', is_array($answers) ? count($answers) : 0));

        // Traiter chaque question
        foreach ($questions as $question) {
            $totalPoints += $question->getPoints();
            $questionId = $question->getId();

            if (isset($answers[$questionId])) {
                $answerId = $answers[$questionId];
                $answer = $em->getRepository(Answer::class)->find($answerId);

                if ($answer && $answer->isCorrect()) {
                    $score += $question->getPoints();
                    $correctAnswers++;
                }

                // Enregistrer la réponse de l'utilisateur
                $userAnswer = new UserAnswer();
                $userAnswer->setUser($user);
                $userAnswer->setQuestion($question);
                $userAnswer->setAnswer($answer);
                $userAnswer->setIsCorrect($answer && $answer->isCorrect());
                $em->persist($userAnswer);
            } else {
                // Créer une réponse vide pour les questions non répondues
                $userAnswer = new UserAnswer();
                $userAnswer->setUser($user);
                $userAnswer->setQuestion($question);
                $userAnswer->setAnswer(null);
                $userAnswer->setIsCorrect(false);
                $em->persist($userAnswer);
            }
        }

        // Calculer le score final
        $finalScore = $totalPoints > 0 ? ($score / $totalPoints) * 100 : 0;
        $scoreMinimum = 70; // Score minimum pour réussir (70% par défaut)
        $passed = $finalScore >= $scoreMinimum;
        $percentage = round($finalScore, 2);

        // Enregistrer le résultat du quiz
        $quizResult = new QuizResult();
        $quizResult->setUser($user);
        $quizResult->setQuiz($quiz);
        $quizResult->setScore($score);
        $quizResult->setTotalPoints($totalPoints);
        $quizResult->setPassed($passed);
        $quizResult->setCompletedAt(new \DateTimeImmutable());

        $em->persist($quizResult);
        $em->flush();

        // Afficher les résultats à l'utilisateur
        $this->addFlash('info', sprintf('Votre score : %d / %d (%.0f%%)', $score, $totalPoints, $percentage));

        if ($passed) {
            $this->addFlash('success', 'Félicitations ! Vous avez réussi le quiz.');
        } else {
            $this->addFlash('warning', sprintf('Désolé, vous n\'avez pas atteint le score minimum de %d%%.', $scoreMinimum));
        }
        
        return $this->redirectToRoute('app_quiz_results', ['id' => $quiz->getId()]);
    }

    /**
     * Affiche les résultats d'un quiz
     */
    #[Route('/results/{id}', name: 'app_quiz_results', methods: ['GET'])]
    public function results(Quiz $quiz, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur a le droit de voir ces résultats
        $cours = $quiz->getCours();
        if (!$cours) {
            $this->addFlash('error', 'Quiz invalide.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Récupérer le dernier résultat de l'utilisateur pour ce quiz
        $quizResult = $em->getRepository(QuizResult::class)->findOneBy(
            ['user' => $user, 'quiz' => $quiz],
            ['completedAt' => 'DESC']
        );
        
        if (!$quizResult) {
            $this->addFlash('error', 'Aucun résultat trouvé pour ce quiz.');
            return $this->redirectToRoute('app_quiz_list', ['id' => $cours->getId()]);
        }
        
        // Récupérer les réponses de l'utilisateur
        $questions = $quiz->getQuestions();
        $userAnswers = [];
        
        foreach ($questions as $question) {
            $userAnswer = $em->getRepository(UserAnswer::class)->findOneBy(
                ['user' => $user, 'question' => $question],
                ['id' => 'DESC']
            );
            
            if ($userAnswer) {
                $userAnswers[$question->getId()] = $userAnswer;
            }
        }
        
        return $this->render('quiz/results.html.twig', [
            'cours' => $cours,
            'quiz' => $quiz,
            'quizResult' => $quizResult,
            'questions' => $questions,
            'userAnswers' => $userAnswers
        ]);
    }

    /**
     * Génère et télécharge un certificat PDF pour un quiz réussi
     */
    #[Route('/certificat/{id}', name: 'app_quiz_certificate')]
    public function certificate(QuizResult $result): Response
    {
        $user = $this->getUser();
        
        // Vérifier les autorisations
        if ($user !== $result->getUser()) {
            throw $this->createAccessDeniedException('Accès non autorisé à ce certificat.');
        }

        if (!$result->isPassed()) {
            $this->addFlash('error', 'Vous devez réussir le quiz pour obtenir un certificat.');
            return $this->redirectToRoute('app_quiz_results', ['id' => $result->getQuiz()->getId()]);
        }

        try {
            // Configuration de Dompdf
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            
            $dompdf = new Dompdf($options);

            // Données pour le template du certificat
            $data = [
                'user' => $result->getUser(),
                'cours' => $result->getQuiz() ? $result->getQuiz()->getCours() : null,
                'score' => ($result->getScore() / $result->getTotalPoints()) * 100,
                'date' => $result->getCompletedAt() ?: new \DateTimeImmutable(),
                'quiz' => $result->getQuiz()
            ];

            // Générer le PDF
            $html = $this->renderView('quiz/certificat.html.twig', $data);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            // Télécharger le PDF
            return new Response(
                $dompdf->output(),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="certificat-'.$result->getId().'.pdf"',
                ]
            );
        } catch (\Exception $e) {
            // En cas d'erreur, afficher une version HTML
            $this->addFlash('warning', 'Impossible de générer le PDF. Affichage en HTML.');
            
            return $this->render('quiz/certificat.html.twig', [
                'user' => $result->getUser(),
                'cours' => $result->getQuiz() ? $result->getQuiz()->getCours() : null,
                'score' => ($result->getScore() / $result->getTotalPoints()) * 100,
                'date' => $result->getCompletedAt() ?: new \DateTimeImmutable(),
                'quiz' => $result->getQuiz()
            ]);
        }
    }
}
