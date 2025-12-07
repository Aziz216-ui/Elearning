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
use App\Service\QuizNotificationService;
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

        // Vérifier si l'utilisateur a accès au cours
        if (!$panierRepository->isCourseInUserPanier($user, $cours)) {
            $this->addFlash('error', 'Vous devez d\'abord ajouter ce cours à votre panier.');
            return $this->redirectToRoute('app_dashboard');
        }

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
    public function start(
        Quiz $quiz,
        Request $request,
        EntityManagerInterface $em,
        PanierRepository $panierRepository,
        QuizNotificationService $notificationService
    ): Response {
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

        // Vérifier si l'étudiant a déjà commencé ce quiz
        $existingResult = $em->getRepository(QuizResult::class)->findOneBy([
            'user' => $user,
            'quiz' => $quiz
        ]);

        if ($existingResult === null) {
            // Créer une nouvelle session de quiz
            $quizResult = new QuizResult();
            $quizResult->setUser($user);
            $quizResult->setQuiz($quiz);
            $quizResult->setStartedAt(new \DateTimeImmutable());

            $em->persist($quizResult);
            $em->flush();

            // Stocker l'ID de la session en session utilisateur
            $request->getSession()->set('quiz_result_id', $quizResult->getId());
        } else {
            // L'utilisateur reprend un quiz commencé
            $request->getSession()->set('quiz_result_id', $existingResult->getId());
            $quizResult = $existingResult;
        }

        // Envoyer la notification aux admins via webhook (à chaque fois)
        try {
            $notificationService->notifyQuizStarted($quizResult);
            
            // Log pour debug
            error_log('Notification envoyée pour quiz ID: ' . $quiz->getId() . ' par user ID: ' . $user->getId());
            
        } catch (\Exception $e) {
            error_log('ERREUR notification: ' . $e->getMessage());
        }
        
        // Créer une notification directement pour l'admin connecté
        try {
            $notification = new \App\Entity\Notification();
            $notification->setAdmin($user);
            $notification->setTitle('Quiz commencé');
            $notification->setMessage(sprintf('L\'étudiant %s a commencé le quiz "%s"', $user->getEmail(), $quiz->getTitle()));
            $notification->setType('quiz_started');
            $notification->setData([
                'quiz_result_id' => $quizResult->getId(),
                'student' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getName() . ' ' . $user->getLastname()
                ],
                'quiz' => [
                    'id' => $quiz->getId(),
                    'title' => $quiz->getTitle()
                ]
            ]);
            
            $em->persist($notification);
            $em->flush();
            
            $this->addFlash('success', 'Quiz commencé - Notification créée avec succès !');
            
        } catch (\Exception $e) {
            error_log('ERREUR création notification directe: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la création de la notification');
        }

        $questions = $em->getRepository(Question::class)->findBy(['quiz' => $quiz]);

        return $this->render('quiz/quiz.html.twig', [
            'cours' => $cours,
            'quiz' => $quiz,
            'questions' => $questions,
            'timeLimit' => $quiz->getTimeLimit() ?? 1800
        ]);
    }

    /**
     * Soumettre les réponses d'un quiz
     */
    #[Route('/submit/{id}', name: 'app_quiz_submit', methods: ['POST'])]
    public function submit(
        Quiz $quiz,
        Request $request,
        EntityManagerInterface $em,
        PanierRepository $panierRepository,
        QuizNotificationService $notificationService
    ): Response {
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

        // Récupérer ou créer la session de quiz
        $quizResultId = $request->getSession()->get('quiz_result_id');
        $quizResult = null;
        
        if ($quizResultId) {
            $quizResult = $em->getRepository(QuizResult::class)->find($quizResultId);
        }

        if (!$quizResult) {
            $quizResult = new QuizResult();
            $quizResult->setUser($user);
            $quizResult->setQuiz($quiz);
            $quizResult->setStartedAt(new \DateTimeImmutable());
        }

        // Traiter chaque question
        foreach ($questions as $question) {
            $totalPoints += $question->getPoints();
            $questionId = $question->getId();
            $isBooleanQuestion = $question->getType() === 'boolean';

            if (isset($answers[$questionId])) {
                $answerId = $answers[$questionId];
                $answer = null;
                $isCorrect = false;

                if ($isBooleanQuestion) {
                    // Pour les questions booléennes, on récupère la réponse correcte depuis la base
                    $correctAnswer = $question->getAnswers()->filter(function($a) {
                        return $a->isCorrect();
                    })->first();
                    
                    // Si l'utilisateur a coché "Vrai" (réponse non vide), on vérifie si c'est correct
                    if (!empty($answerId) && $correctAnswer) {
                        $isCorrect = true;
                        $answer = $correctAnswer;
                    } else if (empty($answerId) && !$correctAnswer) {
                        // Si l'utilisateur a coché "Faux" (réponse vide) et qu'il n'y a pas de réponse correcte
                        $isCorrect = true;
                    }
                } else {
                    // Comportement normal pour les autres types de questions
                    $answer = $em->getRepository(Answer::class)->find($answerId);
                    $isCorrect = $answer && $answer->isCorrect();
                }

                if ($isCorrect) {
                    $score += $question->getPoints();
                    $correctAnswers++;
                }

                // Enregistrer la réponse de l'utilisateur
                $userAnswer = new UserAnswer();
                $userAnswer->setUser($user);
                $userAnswer->setQuestion($question);
                $userAnswer->setAnswer($answer);
                $userAnswer->setIsCorrect($isCorrect);
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
        $quizResult->setScore($score);
        $quizResult->setTotalPoints($totalPoints);
        $quizResult->setPassed($passed);
        $quizResult->setCompletedAt(new \DateTimeImmutable());

        $em->persist($quizResult);
        $em->flush();

        // Envoyer la notification de complétion aux admins
        $notificationService->notifyQuizCompleted($quizResult);

        // Effacer la session du quiz
        $request->getSession()->remove('quiz_result_id');

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
