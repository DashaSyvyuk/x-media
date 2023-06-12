<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OrderStatus extends Constraint
{
    public string $message = '';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
