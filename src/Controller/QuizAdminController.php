<?php



namespace App\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Quiz;
use App\Entity\Question;        // <-- AJOUTE CECI
use App\Entity\Answer;          // <-- AJOUTE CECI
use App\Form\QuizType;
use App\Repository\QuizRepository;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/admin/quiz')]
class QuizAdminController extends AbstractController
{
    #[Route('/', name: 'app_quiz_admin_index', methods: ['GET'])]
    public function index(QuizRepository $quizRepository, CoursRepository $coursRepository): Response
    {
        $quizzes = $quizRepository->findAll();
        $courses = $coursRepository->findAll();

        return $this->render('quiz_admin/quiz_management.html.twig', [
            'quizzes' => $quizzes,
            'courses' => $courses,
        ]);
    }

    #[Route('/new', name: 'app_quiz_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $request->request->all();

            if (isset($data['questions'])) {
                foreach ($data['questions'] as $qKey => $qData) {

                    if (!preg_match('/^q\d+$/', $qKey)) continue;

                    $question = new Question();
                    $question->setQuiz($quiz);

                    if (isset($qData['text'])) $question->setText($qData['text']);
                    if (isset($qData['points'])) $question->setPoints((int)$qData['points']);

                    $question->setType('multiple');

                    // ---- Réponses ----
                    if (isset($qData['answers'])) {

                        $hasCorrect = false;

                        foreach ($qData['answers'] as $aKey => $aData) {
                            if (!preg_match('/^a\d+$/', $aKey)) continue;

                            $answer = new Answer();
                            $answer->setText($aData['text'] ?? '');
                            $answer->setIsCorrect(isset($aData['isCorrect']));
                            $question->addAnswer($answer);

                            if ($answer->isCorrect()) {
                                $hasCorrect = true;
                            }
                        }

                        if (!$hasCorrect && $question->getAnswers()->count() > 0) {
                            $question->getAnswers()->first()->setIsCorrect(true);
                        }
                    }

                    $quiz->addQuestion($question);
                }
            }

            // calcul total points
            $total = 0;
            foreach ($quiz->getQuestions() as $q) {
                $total += $q->getPoints();
            }
            $quiz->setTotalPoints($total);

            $entityManager->persist($quiz);
            $entityManager->flush();

            $this->addFlash('success', 'Quiz créé avec succès.');
            return $this->redirectToRoute('app_quiz_admin_index');
        }

        return $this->render('quiz_admin/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'app_quiz_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données brutes du formulaire
            $data = $request->request->all();
            
            // Vérifier si nous avons des données de formulaire
            if (isset($data['questions'])) {
                $questionsData = $data['questions'];
                
                // Parcourir les questions du formulaire
                foreach ($questionsData as $questionKey => $questionData) {
                    // Vérifier si c'est une clé valide (commence par 'q' suivi de chiffres)
                    if (preg_match('/^q\d+$/', $questionKey)) {
                        $question = $quiz->getQuestions()[$questionKey] ?? null;
                        if ($question) {
                            // Mettre à jour le texte et les points de la question
                            if (isset($questionData['text'])) {
                                $question->setText($questionData['text']);
                            }
                            if (isset($questionData['points'])) {
                                $question->setPoints($questionData['points']);
                            }
                            
                            // Traiter les réponses
                            if (isset($questionData['answers'])) {
                                $answerIndex = 0;
                                foreach ($questionData['answers'] as $answerKey => $answerData) {
                                    // Vérifier si c'est une clé valide (commence par 'a' suivi de chiffres)
                                    if (preg_match('/^a\d+$/', $answerKey)) {
                                        $answer = $question->getAnswers()[$answerIndex] ?? null;
                                        if ($answer) {
                                            if (isset($answerData['text'])) {
                                                $answer->setText($answerData['text']);
                                            }
                                            if (isset($answerData['isCorrect'])) {
                                                $answer->setIsCorrect(true);
                                            } else {
                                                $answer->setIsCorrect(false);
                                            }
                                            $answer->setQuestion($question);
                                            $answerIndex++;
                                        }
                                    }
                                }
                            }
                            
                            $question->setQuiz($quiz);
                        }
                    }
                }
            }
            
            // S'assurer qu'il y a au moins une réponse correcte par question
            foreach ($quiz->getQuestions() as $question) {
                $hasCorrectAnswer = false;
                foreach ($question->getAnswers() as $answer) {
                    if ($answer->isCorrect()) {
                        $hasCorrectAnswer = true;
                        break;
                    }
                }
                
                if (!$hasCorrectAnswer && $question->getAnswers()->count() > 0) {
                    $question->getAnswers()->first()->setIsCorrect(true);
                }
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Le quiz a été mis à jour avec succès.');
            return $this->redirectToRoute('app_quiz_admin_index');
        }

        return $this->render('quiz_admin/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            $entityManager->remove($quiz);
            $entityManager->flush();
            $this->addFlash('success', 'Le quiz a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_quiz_admin_index');
    }
}