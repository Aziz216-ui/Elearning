<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ContainsQuestionValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        // Vérifier si le quiz a au moins une question
        if (count($value->getQuestions()) === 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('questions')
                ->addViolation();
        }
    }
}
