<?php



namespace App\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Quiz;
use App\Entity\Question;        // <-- AJOUTE CECI
use App\Entity\Answer;          // <-- AJOUTE CECI
use App\Form\QuizType;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/admin/quiz')]
class QuizAdminController extends AbstractController
{
    #[Route('/', name: 'app_quiz_admin_index', methods: ['GET'])]
    public function index(QuizRepository $quizRepository): Response
    {
        $quizzes = $quizRepository->findAll();

        return $this->render('quiz_admin/index.html.twig', [
            'quizzes' => $quizzes,
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

            // Vérifier si nous avons des données de questions
            if (isset($data['questions'])) {
                foreach ($data['questions'] as $qKey => $qData) {
                    // Accepter toutes les clés de questions (pas seulement celles qui commencent par q)
                    if (!is_array($qData) || !isset($qData['text'])) continue;

                    $question = new Question();
                    $question->setQuiz($quiz);

                    if (isset($qData['text'])) $question->setText($qData['text']);
                    if (isset($qData['points'])) $question->setPoints((int)$qData['points']);

                    $question->setType($qData['type'] ?? 'multiple');

                    // ---- Réponses ----
                    if (isset($qData['answers'])) {
                        $hasCorrect = false;

                        foreach ($qData['answers'] as $aKey => $aData) {
                            if (!is_array($aData) || !isset($aData['text'])) continue;

                            $answer = new Answer();
                            $answer->setText($aData['text'] ?? '');
                            // Corriger le nom du champ : 'correct' au lieu de 'isCorrect'
                            $answer->setIsCorrect(isset($aData['correct']));
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
            
            // Vérifier si nous avons des données de questions
            if (isset($data['questions'])) {
                // Supprimer les anciennes questions et réponses
                foreach ($quiz->getQuestions() as $oldQuestion) {
                    // D'abord supprimer les user_answer qui référencent les réponses de cette question
                    foreach ($oldQuestion->getAnswers() as $answer) {
                        $userAnswers = $entityManager->getRepository('App\Entity\UserAnswer')->findBy(['answer' => $answer]);
                        foreach ($userAnswers as $userAnswer) {
                            $entityManager->remove($userAnswer);
                        }
                        $entityManager->remove($answer);
                    }
                    $entityManager->remove($oldQuestion);
                }
                
                // Créer les nouvelles questions
                foreach ($data['questions'] as $qKey => $qData) {
                    if (!is_array($qData) || !isset($qData['text'])) continue;

                    $question = new Question();
                    $question->setQuiz($quiz);

                    if (isset($qData['text'])) $question->setText($qData['text']);
                    if (isset($qData['points'])) $question->setPoints((int)$qData['points']);
                    $question->setType($qData['type'] ?? 'multiple');

                    // Traiter les réponses
                    if (isset($qData['answers'])) {
                        $hasCorrect = false;

                        foreach ($qData['answers'] as $aKey => $aData) {
                            if (!is_array($aData) || !isset($aData['text'])) continue;

                            $answer = new Answer();
                            $answer->setText($aData['text'] ?? '');
                            $answer->setIsCorrect(isset($aData['correct']));
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
            
            // Recalculer le total des points
            $total = 0;
            foreach ($quiz->getQuestions() as $q) {
                $total += $q->getPoints();
            }
            $quiz->setTotalPoints($total);
            
            $entityManager->flush();

            $this->addFlash('success', 'Le quiz a été mis à jour avec succès.');
            return $this->redirectToRoute('app_quiz_admin_index');
        }

        return $this->render('quiz_admin/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle-visibility', name: 'app_quiz_admin_toggle_visibility', methods: ['POST'])]
    public function toggleVisibility(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_visibility'.$quiz->getId(), $request->request->get('_token'))) {
            $quiz->setIsVisible(!$quiz->isVisible());
            $entityManager->flush();
            $this->addFlash('success', sprintf('Le quiz a été marqué comme %s avec succès.', $quiz->isVisible() ? 'visible' : 'masqué'));
        }

        return $this->redirectToRoute('app_quiz_admin_index');
    }

    #[Route('/{id}', name: 'app_quiz_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            // Supprimer d'abord les user_answer liés aux réponses des questions
            foreach ($quiz->getQuestions() as $question) {
                foreach ($question->getAnswers() as $answer) {
                    $userAnswers = $entityManager->getRepository('App\Entity\UserAnswer')->findBy(['answer' => $answer]);
                    foreach ($userAnswers as $userAnswer) {
                        $entityManager->remove($userAnswer);
                    }
                    $entityManager->remove($answer);
                }
                $entityManager->remove($question);
            }
            
            $entityManager->remove($quiz);
            $entityManager->flush();
            $this->addFlash('success', 'Le quiz a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_quiz_admin_index');
    }
}