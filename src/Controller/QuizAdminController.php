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
    #[Route('/results', name: 'app_quiz_admin_results', methods: ['GET'])]
    public function results(Request $request, QuizRepository $quizRepository): Response
    {
        $searchTerm = $request->query->get('q', '');
        $visibility = $request->query->get('visibility', '');
        
        // Build query
        $qb = $quizRepository->createQueryBuilder('q');
        
        // Add search filter
        if (!empty($searchTerm)) {
            $qb->andWhere('q.title LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }
        
        // Add visibility filter
        if ($visibility === 'visible') {
            $qb->andWhere('q.is_visible = :visible')
               ->setParameter('visible', true);
        } elseif ($visibility === 'hidden') {
            $qb->andWhere('q.is_visible = :visible')
               ->setParameter('visible', false);
        }
        
        $quizzes = $qb->getQuery()->getResult();
        
        return $this->render('quiz_admin/_results.html.twig', [
            'quizzes' => $quizzes,
        ]);
    }

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
        // Set default values for new quiz
        $quiz->setIsVisible(true);
        $quiz->setIsPublished(false);
        
        $form = $this->createForm(QuizType::class, $quiz);
        
        // Handle form submission
        $form->handleRequest($request);
        
        // Get raw POST data to check for questions
        $data = $request->request->all();

        // Log the entire request data for debugging
        error_log('===== REQUEST DATA =====');
        error_log(print_r($data, true));

        // Handle both formats: direct 'questions', 'quiz[questions]', and form submission format
        $questionsData = [];
        
        // First check if we have the expected quiz[questions] structure
        if (isset($data['quiz'])) {
            // Look for any keys that contain 'questions' in the quiz data
            foreach ($data['quiz'] as $key => $value) {
                if (strpos($key, 'questions') !== false && is_array($value)) {
                    $questionsData = $value;
                    error_log('Found questions data in quiz[' . $key . '] with ' . count($value) . ' questions');
                    break;
                }
            }
            
            // If no questions array found, try to reconstruct from individual fields
            if (empty($questionsData)) {
                error_log('No questions array found, trying to reconstruct from individual fields...');
                $questionsData = $this->reconstructQuestionsFromFields($data['quiz']);
            }
        }
        
        // Fallback to other formats
        if (empty($questionsData) && isset($data['questions'])) {
            $questionsData = $data['questions'];
        } elseif (empty($questionsData) && isset($data['quiz']['questions'])) {
            $questionsData = $data['quiz']['questions'];
        }

        // Check if form is submitted
        if ($form->isSubmitted()) {
            // Debug: Log the raw request data
            error_log('Raw POST data: ' . print_r($data, true));
            error_log('Questions data: ' . print_r($questionsData, true));

            // Ensure title is not null
            if ($quiz->getTitle() === null || $quiz->getTitle() === '') {
                $quiz->setTitle(''); // Set empty string instead of null
            }

            // Try direct field extraction if questionsData is empty
            if (empty($questionsData)) {
                error_log('Questions data empty, trying direct extraction...');
                $questionsData = $this->extractQuestionsDirectly($data);
            }

            // Process questions from raw POST data first
            if (is_array($questionsData) && count($questionsData) > 0) {
                error_log('Processing questions...');
                error_log('Questions data: ' . print_r($questionsData, true));

                // Process each question
                foreach ($questionsData as $qId => $qData) {
                    try {
                        error_log("Processing question: " . print_r($qData, true));

                        $question = new Question();
                        $question->setQuiz($quiz);
                        $question->setText($qData['text'] ?? '');
                        $question->setPoints(isset($qData['points']) ? (int)$qData['points'] : 1);

                        $type = $qData['type'] ?? 'multiple';
                        $question->setType($type);

                        error_log("Question type: " . $type);

                        // Process answers based on question type
                        if ($type === 'boolean') {
                            $this->processTrueFalseQuestion($question, $qData, $entityManager);
                        } elseif ($type === 'text') {
                            $this->processShortAnswerQuestion($question, $qData, $entityManager);
                        } else {
                            $this->processMultipleChoiceQuestion($question, $qData, $entityManager);
                        }

                        $entityManager->persist($question);
                        $quiz->addQuestion($question);

                        error_log("Question added successfully");
                    } catch (\Exception $e) {
                        error_log("Error processing question: " . $e->getMessage());
                        error_log($e->getTraceAsString());
                    }
                }

                error_log('Questions after processing: ' . $quiz->getQuestions()->count());
            } else {
                // Temporary debug: show what we actually received
                error_log('No questions found in POST data. Full data structure:');
                error_log(print_r($data, true));
                
                // Check if there are any question-like fields
                foreach ($data as $key => $value) {
                    if (is_string($key) && strpos($key, 'question') !== false) {
                        error_log('Found question-like field: ' . $key);
                    }
                    if (is_array($value)) {
                        foreach ($value as $subKey => $subValue) {
                            if (is_string($subKey) && strpos($subKey, 'question') !== false) {
                                error_log('Found question-like subfield: ' . $key . '->' . $subKey);
                            }
                        }
                    }
                }
            }

            // Now validate the form with the processed questions
            if ($form->isValid()) {
                // Check if we have any questions after processing


                // Calculate total points
                $total = 0;
                foreach ($quiz->getQuestions() as $q) {
                    $total += $q->getPoints();
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
                            $isCorrectVal = null;
                            if (isset($aData['isCorrect'])) {
                                $isCorrectVal = $aData['isCorrect'];
                            } elseif (isset($aData['correct'])) {
                                $isCorrectVal = $aData['correct'];
                            }
                            $answer->setIsCorrect($isCorrectVal !== null && $isCorrectVal !== 'false');
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

    private function processTrueFalseQuestion(Question $question, array $qData, EntityManagerInterface $entityManager): void
    {
        // For true/false questions, create two predefined answers
        $answerTrue = new Answer();
        $answerTrue->setText('Vrai');
        $answerTrue->setIsCorrect(($qData['correct_answer'] ?? $qData['correctAnswer'] ?? null) === 'true');
        $question->addAnswer($answerTrue);
        $entityManager->persist($answerTrue);

        $answerFalse = new Answer();
        $answerFalse->setText('Faux');
        $answerFalse->setIsCorrect(($qData['correct_answer'] ?? $qData['correctAnswer'] ?? null) === 'false');
        $question->addAnswer($answerFalse);
        $entityManager->persist($answerFalse);
    }

    private function processShortAnswerQuestion(Question $question, array $qData, EntityManagerInterface $entityManager): void
    {
        // For short answer questions, create a single answer that will be validated against user input
        $answer = new Answer();
        $answer->setText($qData['correctAnswer'] ?? '');
        $answer->setIsCorrect(true);
        $question->addAnswer($answer);
        $entityManager->persist($answer);
    }

    private function processMultipleChoiceQuestion(Question $question, array $qData, EntityManagerInterface $entityManager): void
    {
        // Process multiple choice answers
        if (isset($qData['answers']) && is_array($qData['answers'])) {
            $hasCorrect = false;
            
            foreach ($qData['answers'] as $aKey => $aData) {
                if (!is_array($aData) || !isset($aData['text'])) continue;
                
                $answer = new Answer();
                $answer->setText($aData['text'] ?? '');
                $isCorrectVal = null;
                if (isset($aData['isCorrect'])) {
                    $isCorrectVal = $aData['isCorrect'];
                } elseif (isset($aData['correct'])) {
                    $isCorrectVal = $aData['correct'];
                }
                $answer->setIsCorrect($isCorrectVal !== null && $isCorrectVal !== 'false');
                $question->addAnswer($answer);
                $entityManager->persist($answer);

                if ($answer->isCorrect()) {
                    $hasCorrect = true;
                }
            }
            
            // Ensure at least one answer is marked as correct
            if (!$hasCorrect && $question->getAnswers()->count() > 0) {
                $question->getAnswers()->first()->setIsCorrect(true);
            }
        }
    }

    private function reconstructQuestionsFromFields(array $quizData): array
    {
        $questions = [];
        
        // Find all question IDs from field names
        $questionIds = [];
        foreach ($quizData as $fieldName => $value) {
            if (preg_match('/questions\[(\d+)\]/', $fieldName, $matches)) {
                $questionIds[$matches[1]] = $matches[1];
            }
        }
        
        error_log('Found question IDs: ' . implode(', ', $questionIds));
        
        // Reconstruct each question
        foreach ($questionIds as $questionId) {
            $question = [
                'text' => '',
                'type' => 'multiple',
                'points' => 1,
                'answers' => []
            ];
            
            // Get question text
            $textField = "questions[{$questionId}][text]";
            if (isset($quizData[$textField])) {
                $question['text'] = $quizData[$textField];
            }
            
            // Get question type
            $typeField = "questions[{$questionId}][type]";
            if (isset($quizData[$typeField])) {
                $question['type'] = $quizData[$typeField];
            }
            
            // Get question points
            $pointsField = "questions[{$questionId}][points]";
            if (isset($quizData[$pointsField])) {
                $question['points'] = (int)$quizData[$pointsField];
            }
            
            // Get answers
            $answers = [];
            foreach ($quizData as $fieldName => $value) {
                if (preg_match("/questions\[{$questionId}\]\[answers\]\[(\d+)\]\[text\]/", $fieldName, $matches)) {
                    $answerIndex = $matches[1];
                    $answers[$answerIndex]['text'] = $value;
                }
                if (preg_match("/questions\[{$questionId}\]\[answers\]\[(\d+)\]\[isCorrect\]/", $fieldName, $matches)) {
                    $answerIndex = $matches[1];
                    $answers[$answerIndex]['isCorrect'] = $value;
                }
            }
            
            $question['answers'] = array_values($answers);
            
            // Only add question if it has text
            if (!empty($question['text'])) {
                $questions[$questionId] = $question;
                error_log("Reconstructed question {$questionId}: " . print_r($question, true));
            }
        }
        
        error_log('Reconstructed ' . count($questions) . ' questions');
        return $questions;
    }

    private function extractQuestionsDirectly(array $data): array
    {
        $questions = [];
        
        error_log('Extracting questions directly from POST data...');
        
        // Look for any field that contains 'questions' in the name
        $questionFields = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && strpos($key, 'questions') !== false) {
                error_log("Found question field: $key = $value");
                $questionFields[$key] = $value;
            }
        }
        
        // Group fields by question ID
        $groupedFields = [];
        foreach ($questionFields as $fieldName => $fieldValue) {
            if (preg_match('/questions\[(\d+)\]/', $fieldName, $matches)) {
                $questionId = $matches[1];
                if (!isset($groupedFields[$questionId])) {
                    $groupedFields[$questionId] = [];
                }
                $groupedFields[$questionId][$fieldName] = $fieldValue;
            }
        }
        
        error_log('Grouped fields by question ID: ' . print_r($groupedFields, true));
        
        // Build questions from grouped fields
        foreach ($groupedFields as $questionId => $fields) {
            $question = [
                'text' => '',
                'type' => 'multiple',
                'points' => 1,
                'answers' => []
            ];
            
            // Extract question data
            foreach ($fields as $fieldName => $fieldValue) {
                if (preg_match('/questions\[' . $questionId . '\]\[text\]/', $fieldName)) {
                    $question['text'] = $fieldValue;
                } elseif (preg_match('/questions\[' . $questionId . '\]\[type\]/', $fieldName)) {
                    $question['type'] = $fieldValue;
                } elseif (preg_match('/questions\[' . $questionId . '\]\[points\]/', $fieldName)) {
                    $question['points'] = (int)$fieldValue;
                } elseif (preg_match('/questions\[' . $questionId . '\]\[answers\]\[(\d+)\]\[text\]/', $fieldName, $matches)) {
                    $answerIndex = $matches[1];
                    if (!isset($question['answers'][$answerIndex])) {
                        $question['answers'][$answerIndex] = ['text' => '', 'isCorrect' => false];
                    }
                    $question['answers'][$answerIndex]['text'] = $fieldValue;
                } elseif (preg_match('/questions\[' . $questionId . '\]\[answers\]\[(\d+)\]\[isCorrect\]/', $fieldName, $matches)) {
                    $answerIndex = $matches[1];
                    if (!isset($question['answers'][$answerIndex])) {
                        $question['answers'][$answerIndex] = ['text' => '', 'isCorrect' => false];
                    }
                    $question['answers'][$answerIndex]['isCorrect'] = $fieldValue;
                }
            }
            
            // Reindex answers
            $question['answers'] = array_values($question['answers']);
            
            // Only add if question has text
            if (!empty($question['text'])) {
                $questions[$questionId] = $question;
                error_log("Extracted question {$questionId}: " . print_r($question, true));
            }
        }
        
        error_log('Direct extraction found ' . count($questions) . ' questions');
        return $questions;
    }
}