<?php

namespace App\Controller;

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
use Dompdf\Dompdf;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class QuizController extends AbstractController
{
    #[Route('/quiz/{id}', name: 'app_quiz')]
    public function quiz(Cours $cours, EntityManagerInterface $em, PanierRepository $panierRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Vérifier si l'utilisateur a le cours dans son panier
        $user = $this->getUser();
        if (!$panierRepository->isCourseInUserPanier($user, $cours)) {
            $this->addFlash('error', 'Vous devez d\'abord ajouter ce cours à votre panier.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Récupérer le quiz lié au cours
        $quiz = $cours->getQuizzes()->first();


        if (!$quiz) {
            $this->addFlash('warning', 'Aucun quiz disponible pour ce cours.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Récupérer les questions du quiz avec leurs réponses
        $questions = $em->getRepository(Question::class)->findBy(['quiz' => $quiz]);

        // Définir un temps limite par défaut de 10 minutes (600 secondes) si non défini
        $timeLimit = $quiz->getTimeLimit() ?? 600;

        return $this->render('quiz/quiz.html.twig', [
            'cours' => $cours,
            'quiz' => $quiz,
            'questions' => $questions,
            'timeLimit' => $timeLimit
        ]);
    }
    #[Route('/quiz/submit/{id}', name: 'app_quiz_submit', methods:['POST'])]
    public function submit(Cours $cours, Request $request, EntityManagerInterface $em, PanierRepository $panierRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        // Vérifier si l'utilisateur a le droit de passer ce quiz
        if (!$panierRepository->isCourseInUserPanier($user, $cours)) {
            $this->addFlash('error', 'Accès non autorisé à ce quiz.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Récupérer le temps passé sur le quiz
        $timeSpent = (int) $request->request->get('timeSpent', 0);

        $quiz = $cours->getQuizzes()->first();

        if (!$quiz) {
            $this->addFlash('error', 'Aucun quiz trouvé pour ce cours.');
            return $this->redirectToRoute('app_dashboard');
        }

        $questions = $quiz->getQuestions();
        $score = 0;
        $totalPoints = 0;
        $correctAnswers = 0;
        $totalQuestions = $questions->count();

        $postData = $request->request->all();
        $answers = $postData['answers'] ?? [];

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

                $userAnswer = new UserAnswer();
                $userAnswer->setUser($user);
                $userAnswer->setQuestion($question);
                $userAnswer->setAnswer($answer);
                $userAnswer->setIsCorrect($answer && $answer->isCorrect());
                $em->persist($userAnswer);
            }
        }

        $finalScore = $totalPoints > 0 ? ($score / $totalPoints) * 100 : 0;
        $passed = $finalScore >= 80;

        $quizResult = new QuizResult();
        $quizResult->setUser($user);
        $quizResult->setQuiz($quiz);
        $quizResult->setScore($score);
        $quizResult->setTotalPoints($totalPoints);
        $quizResult->setPassed($passed);
        $quizResult->setCompletedAt(new \DateTimeImmutable());

        $em->persist($quizResult);
        $em->flush();

        if ($passed) {
            $this->addFlash('success', 'Bravo ! Vous avez réussi le quiz. Vous pouvez maintenant télécharger votre certificat.');
        } else {
            $this->addFlash('error', 'Une ou plusieurs réponses sont incorrectes. Réessayez !');
        }

        return $this->redirectToRoute('app_quiz_results', ['id' => $quiz->getId()]);
    }


    #[Route('/quiz/{id}/results', name: 'app_quiz_results', methods: ['GET'])]
    public function results(Quiz $quiz, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        // Récupérer le résultat du quiz pour l'utilisateur actuel
        $quizResult = $em->getRepository(QuizResult::class)->findOneBy(
            ['user' => $user, 'quiz' => $quiz],
            ['completedAt' => 'DESC']
        );
        
        if (!$quizResult) {
            $this->addFlash('error', 'Aucun résultat trouvé pour ce quiz.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Récupérer les questions avec les réponses de l'utilisateur
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
            'quiz' => $quiz,
            'quizResult' => $quizResult,
            'questions' => $questions,
            'userAnswers' => $userAnswers
        ]);
    }

    #[Route('/certificat/{id}', name: 'app_certificat_pdf')]
    public function certificat(QuizResult $result): Response
    {
        // Vérifier si l'utilisateur actuel est le propriétaire du résultat
        if ($this->getUser() !== $result->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir ce certificat.');
        }

        // Créer une nouvelle instance Dompdf
        $dompdf = new Dompdf();

        // Générer le HTML pour le certificat
        $html = $this->renderView('quiz/certificat.html.twig', [
            'user' => $result->getUser(),
            'cours' => $result->getQuiz() ? $result->getQuiz()->getCours() : null,
            'score' => $result->getScore(),
            'date' => $result->getCompletedAt() ?: new \DateTimeImmutable()
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);

        // Définir la taille et l'orientation du papier
        $dompdf->setPaper('A4', 'portrait');

        // Rendre le HTML en PDF
        $dompdf->render();

        // Télécharger le PDF généré
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="certificat-'.$result->getId().'.pdf"',
            ]
        );
    }
}
