<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OrderAddress extends Constraint
{
    public string $message = '';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
