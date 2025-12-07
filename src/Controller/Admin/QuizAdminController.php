<?php



namespace App\Controller\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Quiz;
use App\Entity\Question;        // <-- AJOUTE CECI
use App\Entity\Answer;          // <-- AJOUTE CECI
use App\Entity\Cours;
use App\Form\QuizType;
use App\Form\QuizSimpleType;
use App\Form\QuestionType;
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
    public function index(Request $request, QuizRepository $quizRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération des paramètres de filtrage
        $searchTerm = trim((string) $request->query->get('q', ''));
        $visibility = $request->query->get('visibility');
        $coursId = $request->query->get('cours');
        
        // Gestion de la visibilité
        $isVisible = null;
        if ($visibility === 'visible') $isVisible = true;
        if ($visibility === 'hidden') $isVisible = false;

        // Récupération des cours pour le filtre
        $coursList = $entityManager->getRepository(Cours::class)->findAll();
        $selectedCours = null;
        
        if ($coursId) {
            $selectedCours = $entityManager->getRepository(Cours::class)->find($coursId);
        }

        // Récupération des quiz avec les filtres
        $quizzes = $quizRepository->searchByCriteria(
            $searchTerm ?: null, 
            $selectedCours, 
            $isVisible
        );

        return $this->render('quiz_admin/index.html.twig', [
            'quizzes' => $quizzes,
            'searchTerm' => $searchTerm,
            'selectedVisibility' => $visibility,
            'coursList' => $coursList,
            'selectedCoursId' => $coursId,
        ]);
    }

    #[Route('/results', name: 'app_quiz_admin_results', methods: ['GET'])]
    public function results(Request $request, QuizRepository $quizRepository): Response
    {
        // Endpoint AJAX pour renvoyer uniquement le tableau filtré
        $searchTerm = trim((string) $request->query->get('q', ''));
        $visibility = $request->query->get('visibility');
        $isVisible = null;
        if ($visibility === 'visible') $isVisible = true;
        if ($visibility === 'hidden') $isVisible = false;

        $quizzes = $quizRepository->searchByCriteria($searchTerm ?: null, null, $isVisible);

        return $this->render('quiz_admin/_quiz_table.html.twig', [
            'quizzes' => $quizzes
        ]);
    }


    

    #[Route('/new-simple', name: 'app_quiz_admin_new_simple', methods: ['GET', 'POST'])]
    public function newSimple(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizSimpleType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($quiz);
            $entityManager->flush();

            $this->addFlash('success', 'Quiz créé avec succès !');
            return $this->redirectToRoute('app_quiz_admin_index');
        } else {
            if ($form->isSubmitted()) {
                error_log('Form errors: ' . $form->getErrors(true));
                $this->addFlash('error', 'Formulaire invalide. Vérifiez les champs.');
            }
        }

        return $this->render('quiz_admin/new_simple.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_quiz_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quiz = new Quiz();
        // valeurs par défaut
        $quiz->setIsVisible(true);
        $quiz->setIsPublished(false);

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // au moins une question


            // total des points
            $total = 0;
            foreach ($quiz->getQuestions() as $q) {
                $total += (int) $q->getPoints();
            }
            $quiz->setTotalPoints($total);

            try {
                $entityManager->persist($quiz);
                $entityManager->flush();

                $this->addFlash('success', 'Quiz créé avec succès.');
                return $this->redirectToRoute('app_quiz_admin_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création du quiz: ' . $e->getMessage());
            }
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
            if (isset($data['quiz']['questions']) && is_array($data['quiz']['questions'])) {
                $questionsData = $data['quiz']['questions'];

                // Parcourir les questions du formulaire
                foreach ($questionsData as $questionKey => $questionData) {
                    $question = null;

                    // Resolver des clés: numeric index, q-prefixed, or id provided
                    if (is_numeric($questionKey)) {
                        $question = $quiz->getQuestions()->get((int)$questionKey);
                    } elseif (is_string($questionKey) && preg_match('/^q(\d+)$/', $questionKey, $m)) {
                        $question = $quiz->getQuestions()->get((int)$m[1]);
                    } elseif (isset($questionData['id']) && is_numeric($questionData['id'])) {
                        $question = $entityManager->getRepository(Question::class)->find((int)$questionData['id']);
                    }

                    if (!$question) {
                        // Pas de question existante à mettre à jour
                        continue;
                    }

                    // Mettre à jour le texte et les points de la question
                    if (isset($questionData['text'])) {
                        $question->setText($questionData['text']);
                    }
                    if (isset($questionData['points'])) {
                        $question->setPoints((int)$questionData['points']);
                    }

                    // Traiter les réponses si présentes
                    if (isset($questionData['answers']) && is_array($questionData['answers'])) {
                        foreach ($questionData['answers'] as $answerKey => $answerData) {
                            $answer = null;
                            if (is_numeric($answerKey)) {
                                $answer = $question->getAnswers()->get((int)$answerKey);
                            } elseif (isset($answerData['id']) && is_numeric($answerData['id'])) {
                                $answer = $entityManager->getRepository(Answer::class)->find((int)$answerData['id']);
                            }

                            if (!$answer) {
                                continue; // nothing to update
                            }

                            if (isset($answerData['text'])) {
                                $answer->setText($answerData['text']);
                            }
                            $answer->setIsCorrect(isset($answerData['isCorrect']) && $answerData['isCorrect'] !== '0');
                            $answer->setQuestion($question);
                        }
                    }

                    $question->setQuiz($quiz);
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

    #[Route('/{id}/toggle-visibility', name: 'app_quiz_admin_toggle_visibility', methods: ['POST'])]
    public function toggleVisibility(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_visibility'.$quiz->getId(), $request->request->get('_token'))) {
            $quiz->setIsVisible(!$quiz->isVisible());
            $quiz->setIsPublished(!$quiz->isPublished()); // Publie aussi le quiz
            $entityManager->flush();
            $this->addFlash('success', sprintf('Le quiz a été marqué comme %s et publié avec succès.', $quiz->isVisible() ? 'visible' : 'masqué'));
        }

        return $this->redirectToRoute('app_quiz_admin_index');
    }

    #[Route('/{id}/delete', name: 'app_quiz_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            // 1) Supprimer d'abord les UserAnswer liés aux questions de ce quiz (FK sur question_id)
            $dqlQuestions = 'DELETE FROM App\\Entity\\UserAnswer ua WHERE ua.question IN (
                                SELECT q FROM App\\Entity\\Question q WHERE q.quiz = :quiz
                            )';

            $entityManager->createQuery($dqlQuestions)
                ->setParameter('quiz', $quiz)
                ->execute();

            // 2) Supprimer ensuite les UserAnswer liés aux réponses de ce quiz (FK sur answer_id), par sécurité
            $dqlAnswers = 'DELETE FROM App\\Entity\\UserAnswer ua WHERE ua.answer IN (
                               SELECT a FROM App\\Entity\\Answer a JOIN a.question q WHERE q.quiz = :quiz
                           )';

            $entityManager->createQuery($dqlAnswers)
                ->setParameter('quiz', $quiz)
                ->execute();

            // 3) Puis supprimer le quiz (et ses questions/réponses via la configuration Doctrine)
            $entityManager->remove($quiz);

            $entityManager->flush();
            $this->addFlash('success', 'Le quiz a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_quiz_admin_index');
    }

    #[Route('/{id}/add-question', name: 'app_quiz_admin_add_question', methods: ['GET', 'POST'])]
    public function addQuestion(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        $question = new Question();
        $question->setQuiz($quiz);
        $form = $this->createForm(QuestionType::class, $question);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($question);
            
            // Mise à jour du total des points du quiz
            $totalPoints = $quiz->getTotalPoints() + $question->getPoints();
            $quiz->setTotalPoints($totalPoints);
            
            $entityManager->flush();
            
            $this->addFlash('success', 'La question a été ajoutée avec succès.');
            return $this->redirectToRoute('app_quiz_admin_edit', ['id' => $quiz->getId()]);
        }
        
        return $this->render('quiz_admin/add_question.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }
}