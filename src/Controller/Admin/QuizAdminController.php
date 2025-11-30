<?php



namespace App\Controller\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Quiz;
use App\Entity\Question;        // <-- AJOUTE CECI
use App\Entity\Answer;          // <-- AJOUTE CECI
use App\Form\QuizType;
use App\Form\QuizSimpleType;
use App\Form\QuestionType;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;


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

    #[Route('/new-simple', name: 'app_quiz_admin_new_simple', methods: ['GET', 'POST'])]
    public function newSimple(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizSimpleType::class, $quiz);
        $form->handleRequest($request);

        $isSubmitted = $form->isSubmitted();
        
        if ($isSubmitted && $form->isValid()) {
            // Validation des contraintes
            $errors = $validator->validate($quiz, null, ['Default']);
            
            // Validation personnalisée pour les questions
            if (count($quiz->getQuestions()) === 0) {
                $this->addFlash('error', 'Vous devez ajouter au moins une question au quiz.');
                return $this->render('quiz_admin/new_simple.html.twig', [
                    'quiz' => $quiz,
                    'form' => $form->createView(),
                ]);
            }
            
            if (count($errors) > 0) {
                // Afficher les erreurs de validation
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('quiz_admin/new_simple.html.twig', [
                    'quiz' => $quiz,
                    'form' => $form->createView(),
                ]);
            }
            
            $entityManager->persist($quiz);
            $entityManager->flush();

            // Vérifier si l'ID a été généré
            if ($quiz->getId()) {
                $this->addFlash('success', 'Quiz créé avec succès ! ID: ' . $quiz->getId());
            } else {
                $this->addFlash('error', 'Erreur: Le quiz n\'a pas d\'ID après la sauvegarde.');
            }
            
            return $this->redirectToRoute('app_quiz_admin_index');
        } else {
            // Debug: Afficher les erreurs si le formulaire n'est pas valide
            if ($form->isSubmitted()) {
                dump($form->getErrors(true));
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
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $quiz = new Quiz();
        // Set default values for new quiz
        $quiz->setIsVisible(true);
        $quiz->setIsPublished(false);
        
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        // Ne rien faire tant que le formulaire n'est pas soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $request->request->all();

            // Les champs dynamiques des questions sont postés au niveau racine sous "questions[...]"
            $questionsData = $data['questions'] ?? null;
            if (is_array($questionsData)) {
                foreach ($questionsData as $qKey => $qData) {
                    $question = new Question();
                    $question->setQuiz($quiz);

                    // Texte et points
                    $question->setText($qData['text'] ?? '');
                    $question->setPoints(isset($qData['points']) ? (int) $qData['points'] : 1);

                    $type = $qData['type'] ?? 'multiple_choice';

                    // Gérer selon le type
                    switch ($type) {
                        case 'true_false':
                            $question->setType('boolean');
                            $correct = $qData['correct_answer'] ?? null;

                            $answerTrue = new Answer();
                            $answerTrue->setText('Vrai');
                            $answerTrue->setIsCorrect($correct === 'true');
                            $answerTrue->setQuestion($question);
                            $question->addAnswer($answerTrue);
                            $entityManager->persist($answerTrue);

                            $answerFalse = new Answer();
                            $answerFalse->setText('Faux');
                            $answerFalse->setIsCorrect($correct === 'false');
                            $answerFalse->setQuestion($question);
                            $question->addAnswer($answerFalse);
                            $entityManager->persist($answerFalse);
                            break;

                        case 'short_answer':
                            $question->setType('text');
                            $expected = $qData['correct_answer'] ?? '';
                            if ($expected !== '') {
                                $a = new Answer();
                                $a->setText($expected);
                                $a->setIsCorrect(true);
                                $a->setQuestion($question);
                                $question->addAnswer($a);
                                $entityManager->persist($a);
                            }
                            break;

                        case 'multiple_choice':
                        default:
                            $question->setType('multiple');
                            if (isset($qData['answers']) && is_array($qData['answers'])) {
                                $hasCorrect = false;
                                foreach ($qData['answers'] as $aData) {
                                    $answer = new Answer();
                                    $answer->setText($aData['text'] ?? '');
                                    // Le template utilise "correct" (pas isCorrect)
                                    $answer->setIsCorrect(isset($aData['correct']));
                                    $answer->setQuestion($question);
                                    $question->addAnswer($answer);
                                    $entityManager->persist($answer);
                                    if ($answer->isCorrect()) {
                                        $hasCorrect = true;
                                    }
                                }
                                // Si aucune réponse correcte cochée, forcer la première
                                if (!$hasCorrect && $question->getAnswers()->count() > 0) {
                                    $question->getAnswers()->first()->setIsCorrect(true);
                                }
                            }
                            break;
                    }

                    $quiz->addQuestion($question);
                    $entityManager->persist($question);
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
    public function edit(Request $request, Quiz $quiz, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validation personnalisée pour les questions
            if (count($quiz->getQuestions()) === 0) {
                $this->addFlash('error', 'Vous devez ajouter au moins une question au quiz.');
                return $this->render('quiz_admin/new.html.twig', [
                    'quiz' => $quiz,
                    'form' => $form,
                ]);
            }

            if ($form->isValid()) {
                // Récupérer les données brutes du formulaire
                $data = $request->request->all();
                
                if (isset($data['quiz']['questions'])) {
                    $questionsData = $data['quiz']['questions'];
                    $questionIndex = 0;
                    
                    // Parcourir les questions du formulaire
                    foreach ($questionsData as $questionKey => $questionData) {
                        if (preg_match('/^q\d+$/', $questionKey)) {
                            $question = $quiz->getQuestions()[$questionIndex] ?? null;
                            if ($question) {
                                // Mettre à jour le texte et les points de la question
                                if (isset($questionData['text'])) {
                                    $question->setText($questionData['text']);
                                }
                                if (isset($questionData['points'])) {
                                    $question->setPoints((int)$questionData['points']);
                                }
                                
                                // Traiter les réponses
                                if (isset($questionData['answers'])) {
                                    $answerIndex = 0;
                                    foreach ($questionData['answers'] as $answerKey => $answerData) {
                                        if (preg_match('/^a\d+$/', $answerKey)) {
                                            $answer = $question->getAnswers()[$answerIndex] ?? null;
                                            if ($answer) {
                                                if (isset($answerData['text'])) {
                                                    $answer->setText($answerData['text']);
                                                }
                                                $answer->setIsCorrect(isset($answerData['isCorrect']));
                                                $answer->setQuestion($question);
                                                $answerIndex++;
                                            }
                                        }
                                    }
                                }
                                
                                $question->setQuiz($quiz);
                                $questionIndex++;
                            }
                        }
                    }
                }
                
                // S'assurer qu'il y a au moins une réponse correcte par question
                $hasError = false;
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
                    } elseif ($question->getAnswers()->count() === 0) {
                        $hasError = true;
                        $this->addFlash('error', 'Chaque question doit avoir au moins une réponse.');
                        break;
                    }
                }
                
                if ($hasError) {
                    return $this->render('quiz_admin/new.html.twig', [
                        'quiz' => $quiz,
                        'form' => $form->createView(),
                    ]);
                }
                
                // Calculer le total des points
                $totalPoints = 0;
                foreach ($quiz->getQuestions() as $question) {
                    $totalPoints += $question->getPoints();
                }
                $quiz->setTotalPoints($totalPoints);

                try {
                    $entityManager->flush();
                    $this->addFlash('success', 'Le quiz a été mis à jour avec succès.');
                    return $this->redirectToRoute('app_quiz_admin_index');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du quiz : ' . $e->getMessage());
                }
            }
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
        
        $isSubmitted = $form->isSubmitted();
        
        if ($isSubmitted && $form->isValid()) {
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