<?php

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class ContainsQuestion extends Constraint
{
    public string $message = 'Vous devez ajouter au moins une question au quiz.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
