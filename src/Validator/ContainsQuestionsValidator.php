<?php

namespace App\Validator;

use App\Entity\Quiz;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ContainsQuestionsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof Quiz) {
            return;
        }

        $questions = $value->getQuestions();
        
        // Vérifier qu'il y a au moins une question
        if ($questions->count() === 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('questions')
                ->addViolation();
            return;
        }

        // Valider chaque question
        foreach ($questions as $index => $question) {
            $questionText = $question->getText();
            
            // Vérifier que la question a du texte
            if (empty($questionText) || trim($questionText) === '') {
                $this->context->buildViolation('La question ' . ($index + 1) . ' doit avoir un énoncé.')
                    ->atPath('questions[' . $index . '].text')
                    ->addViolation();
                continue;
            }

            // Vérifier que la question a des réponses
            $answers = $question->getAnswers();
            if ($answers->count() === 0) {
                $this->context->buildViolation('La question ' . ($index + 1) . ' doit avoir au moins une réponse.')
                    ->atPath('questions[' . $index . '].answers')
                    ->addViolation();
                continue;
            }

            // Valider les réponses selon le type de question
            $questionType = $question->getType();
            $hasValidAnswer = false;
            $hasCorrectAnswer = false;

            foreach ($answers as $answer) {
                $answerText = $answer->getText();
                
                // Vérifier que la réponse a du texte
                if (empty($answerText) || trim($answerText) === '') {
                    continue;
                }
                
                $hasValidAnswer = true;
                
                // Pour les questions à choix multiple, vérifier qu'il y a une réponse correcte
                if ($questionType === 'multiple_choice' && $answer->isCorrect()) {
                    $hasCorrectAnswer = true;
                }
            }

            if (!$hasValidAnswer) {
                $this->context->buildViolation('La question ' . ($index + 1) . ' doit avoir au moins une réponse valide.')
                    ->atPath('questions[' . $index . '].answers')
                    ->addViolation();
            }

            // Pour les questions à choix multiple, s'assurer qu'il y a une réponse correcte
            if ($questionType === 'multiple_choice' && !$hasCorrectAnswer) {
                $this->context->buildViolation('La question ' . ($index + 1) . ' doit avoir au moins une réponse correcte.')
                    ->atPath('questions[' . $index . '].answers')
                    ->addViolation();
            }

            // Pour les questions vrai/faux, s'assurer qu'il y a une réponse
            if ($questionType === 'true_false') {
                $hasTrueFalse = false;
                foreach ($answers as $answer) {
                    if (in_array(strtolower($answer->getText()), ['true', 'false', 'vrai', 'faux'])) {
                        $hasTrueFalse = true;
                        break;
                    }
                }
                if (!$hasTrueFalse) {
                    $this->context->buildViolation('La question ' . ($index + 1) . ' de type Vrai/Faux doit avoir une réponse valide.')
                        ->atPath('questions[' . $index . '].answers')
                        ->addViolation();
                }
            }

            // Pour les questions à réponse courte, s'assurer qu'il y a une réponse attendue
            if ($questionType === 'short_answer') {
                $hasShortAnswer = false;
                foreach ($answers as $answer) {
                    if (!empty($answer->getText()) && trim($answer->getText()) !== '') {
                        $hasShortAnswer = true;
                        break;
                    }
                }
                if (!$hasShortAnswer) {
                    $this->context->buildViolation('La question ' . ($index + 1) . ' doit avoir une réponse attendue.')
                        ->atPath('questions[' . $index . '].answers')
                        ->addViolation();
                }
            }
        }
    }
}
