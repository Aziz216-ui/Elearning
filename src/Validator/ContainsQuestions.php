<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ContainsQuestions extends Constraint
{
    public string $message = 'Un quiz doit contenir au moins une question avec des réponses valides.';

    public function validatedBy(): string
    {
        return ContainsQuestionsValidator::class;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
