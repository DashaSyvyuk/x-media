<?php

namespace App\Validator;

use App\Entity\Order;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class OrderAddressValidator extends ConstraintValidator
{
    /**
     * @param Order $value
     * @param Constraint $constraint
     *
     * @return bool
     *
     */
    public function validate($value, Constraint $constraint): bool
    {
        if ($value->getSource() === 'Admin') {
            return true;
        }

        if ($value->getDeltype()?->getIsNovaPoshta() && ! $value->getNovaPoshtaCity()) {
            $this->context->buildViolation('order.field.required')->atPath('city')->addViolation();

            return false;
        }

        if ($value->getDeltype()?->getIsNovaPoshta() && ! $value->getNovaPoshtaOffice()) {
            $this->context->buildViolation('order.field.required')->atPath('office')->addViolation();

            return false;
        }

        if ($value->getDeltype()?->getNeedAddressField() && ! $value->getAddress()) {
            $this->context->buildViolation('order.field.required')->atPath('address')->addViolation();

            return false;
        }

        return true;
    }
}
